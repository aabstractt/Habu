<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\round;

use bitrule\practice\duel\impl\SpectatingDuelTrait;
use bitrule\practice\duel\stage\StartingStage;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\TranslationKey;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function str_starts_with;

final class NormalRoundingDuelImpl extends RoundingDuel {
    use SpectatingDuelTrait;

    /**
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerPrepare(Player $player, DuelProfile $duelProfile): void {
        $this->playersSpawn[$player->getXuid()] = count($this->playersSpawn);

        $opponentName = $this->getOpponentName($player->getXuid());

        // TODO: Idk for what using that xd
        $player->sendMessage(TextFormat::RED . 'Opponent: ' . ($opponentName ?? 'None'));
    }

    /**
     * Process the player when the match ends.
     *
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerEnd(Player $player, DuelProfile $duelProfile): void {
        parent::processPlayerEnd($player, $duelProfile);

        if ($this->stage instanceof StartingStage) return;

        $opponent = $this->getOpponent($player);
        if ($opponent === null) return;

        $duelStatistics = $duelProfile->getDuelStatistics();
        $opponentDuelStatistics = $opponent->getDuelStatistics();

        $player->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
            $opponent->getName(),
            '&a(+0)',
            (string) $duelStatistics->getCritics(),
            (string) $duelStatistics->getDamageDealt(),
            (string) $opponentDuelStatistics->getCritics(),
            (string) $opponentDuelStatistics->getDamageDealt(),
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
        unset($this->playersSpawn[$player->getXuid()]);

        if (!$canEnd) return;

        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) return;

        $expectedPlayersAlive = $spawnId > 2 ? 1 : 2;
        if (count($this->getAlive()) > $expectedPlayersAlive) return;

        $this->end();
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