<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\stage\EndingStage;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\duel\stage\StartingStage;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\Practice;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\TranslationKeys;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function count;
use function str_starts_with;

final class NormalDuelImpl extends Duel {

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

        $this->playersSpawn[$player->getXuid()] = self::SPECTATOR_SPAWN_ID;

//        $this->postJoinSpectator($player);
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
        if (!$canEnd) return;

        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) return;

        $expectedPlayersAlive = $spawnId > 2 ? 1 : 2;
        if (count($this->getAlive()) > $expectedPlayersAlive) return;

        $this->end();
    }

    /**
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerPrepare(Player $player, DuelProfile $duelProfile): void {
        $opponentName = $this->getOpponentName($player->getXuid());

        // TODO: Idk for what using that xd
        $player->sendMessage(TextFormat::RED . 'Opponent: ' . ($opponentName ?? 'None'));
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
     * Process the player when the match ends.
     *
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerEnd(Player $player, DuelProfile $duelProfile): void {
        // TODO: Implement processPlayerEnd() method.
    }

    /**
     * @param string $xuid
     *
     * @return string|null
     */
    public function getOpponentName(string $xuid): ?string {
        if ($this->getSpawnId($xuid) === -1) return null;

        foreach ($this->getPlayers() as $duelProfile) {
            if ($duelProfile->getXuid() === $xuid) continue;

            return $duelProfile->getName();
        }

        return null;
    }

    /**
     * @param Player $player
     *
     * @return DuelProfile|null
     */
    public function getOpponent(Player $player): ?DuelProfile {
        if ($this->getSpawnId($player->getXuid()) === -1) return null;

        foreach ($this->getPlayers() as $duelProfile) {
            if ($duelProfile->getXuid() === $player->getXuid()) continue;

            return $duelProfile;
        }

        return null;
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