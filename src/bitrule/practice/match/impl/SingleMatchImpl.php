<?php

declare(strict_types=1);

namespace bitrule\practice\match\impl;

use bitrule\practice\manager\ProfileManager;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\stage\EndingStage;
use bitrule\practice\match\stage\PlayingStage;
use bitrule\practice\match\stage\StartingStage;
use bitrule\practice\Practice;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\TranslationKeys;
use pocketmine\player\Player;
use pocketmine\world\Position;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_search;
use function count;
use function is_int;
use function str_starts_with;

final class SingleMatchImpl extends AbstractMatch {

    /** @var string[] */
    private array $players = [];

    /**
     * This method is called when a player joins the match.
     * Add the player to the match and teleport them to their spawn.
     *
     * @param Player $player
     */
    public function joinSpectator(Player $player): void {
        if (!$this->isLoaded()) {
            throw new RuntimeException('Match not loaded.');
        }

        ProfileManager::getInstance()->addDuelProfile(
            $player,
            $this,
            true
        );

        $this->players[] = $player->getXuid();

        $this->teleportSpawn($player);
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function prepare(array $totalPlayers): void {
        $this->players = array_map(
            fn(Player $player) => $player->getXuid(),
            $totalPlayers
        );
    }

    /**
     * This method is called when the match stage change to Ending.
     * Usually is used to send the match results to the players.
     */
    public function end(): void {
        foreach ($this->getEveryone() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            Practice::setProfileScoreboard($player, ProfileManager::MATCH_ENDING_SCOREBOARD);

            if ($this->stage instanceof StartingStage) continue;

            $opponent = $this->getOpponent($player);
            if ($opponent === null) continue;

            $matchStatistics = $duelProfile->getMatchStatistics();
            $opponentMatchStatistics = $opponent->getMatchStatistics();

            $player->sendMessage(TranslationKeys::MATCH_END_STATISTICS_NORMAL->build(
                $opponent->getName(),
                '&a(+0)',
                (string) $matchStatistics->getCritics(),
                (string) $matchStatistics->getDamageDealt(),
                (string) $opponentMatchStatistics->getCritics(),
                (string) $opponentMatchStatistics->getDamageDealt(),
            ));
        }

        $this->setStage(new EndingStage(
            5,
            $this->stage instanceof PlayingStage ? $this->stage->getSeconds() : 0
        ));
    }

    /**
     * Get the spawn id of the player
     * If is single match the spawn id is the index of the player in the players array.
     * If is team match the spawn id is the team id of the player.
     *
     * @param string $xuid
     *
     * @return int
     */
    public function getSpawnId(string $xuid): int {
        return is_int($spawnId = array_search($xuid, $this->players, true)) ? $spawnId : -1;
    }

    /**
     * @param Player $player
     */
    public function teleportSpawn(Player $player): void {
        if (($spawnId = $this->getSpawnId($player->getXuid())) === -1) {
            throw new RuntimeException('Player not found in match.');
        }

        $player->teleport(Position::fromObject(
            match ($spawnId) {
                0 => $this->arena->getFirstPosition(),
                1 => $this->arena->getSecondPosition(),
                default => $this->getWorld()->getSpawnLocation()
            },
            $this->getWorld()
        ));
    }

    /**
     * Remove a player from the match.
     * Check if the match can end.
     * Usually is checked when the player died or left the match.
     *
     * @param Player $player
     * @param bool   $canEnd
     */
    public function removePlayer(Player $player, bool $canEnd): void {
        if (($spawnId = $this->getSpawnId($player->getXuid())) === -1) return;

        if ($canEnd && count($this->getAlive()) <= ($spawnId > 2 ? 1 : 2)) {
            $this->end();
        }

        unset($this->players[$spawnId]);

        ProfileManager::getInstance()->removeDuelProfile($player);
    }

    /**
     * @return DuelProfile[]
     */
    public function getEveryone(): array {
        return array_filter(
            array_map(
                fn (string $xuid) => ProfileManager::getInstance()->getDuelProfile($xuid),
                $this->players
            ),
            fn(?DuelProfile $duelProfile) => $duelProfile !== null
        );
    }

    /**
     * @param string $xuid
     *
     * @return string|null
     */
    public function getOpponentName(string $xuid): ?string {
        if (($spawnId = $this->getSpawnId($xuid)) === -1) return null;

        $opponentXuid = match ($spawnId) {
            0 => $this->players[1] ?? null,
            1 => $this->players[0] ?? null,
            default => null
        };

        return $opponentXuid === null ? null : ProfileManager::getInstance()->getDuelProfile($opponentXuid)?->getName();
    }

    /**
     * @param Player $player
     *
     * @return DuelProfile|null
     */
    public function getOpponent(Player $player): ?DuelProfile {
        if (($spawnId = $this->getSpawnId($player->getXuid())) === -1) return null;

        $opponentXuid = match ($spawnId) {
            0 => $this->players[1] ?? null,
            1 => $this->players[0] ?? null,
            default => null
        };
        if ($opponentXuid === null) return null;

        return ProfileManager::getInstance()->getDuelProfile($opponentXuid);
    }

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        $parent = parent::replacePlaceholders($player, $identifier);
        if ($parent !== null) return $parent;

        if (str_starts_with($identifier, 'match_opponent')) {
            $opponent = $this->getOpponent($player);
            if ($opponent === null) return null;

            $instance = $opponent->toPlayer();
            if ($instance === null || !$instance->isOnline()) return null;

            return $identifier === 'match_opponent_name' ? $opponent->getName() : (string) $instance->getNetworkSession()->getPing();
        }

        return null;
    }
}