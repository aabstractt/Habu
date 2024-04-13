<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\round;

use bitrule\practice\duel\stage\StartingStage;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\TranslationKeys;
use pocketmine\player\Player;
use function str_starts_with;

final class NormalRoundingDuelImpl extends RoundingDuel {

    /**
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerPrepare(Player $player, DuelProfile $duelProfile): void {
        // TODO: Implement processPlayerPrepare() method.
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

    /**
     * Remove a player from the match.
     * Check if the match can end.
     * Usually is checked when the player died or left the match.
     *
     * @param Player $player
     * @param bool   $canEnd
     */
    public function removePlayer(Player $player, bool $canEnd): void {
        // TODO: Implement removePlayer() method.
    }

    /**
     * Let the server know if the duel
     * can be re-duel.
     *
     * @param DuelProfile[] $players
     *
     * @return bool
     */
    protected function canReDuel(array $players): bool {
        // TODO: Implement canReDuel() method.
}

    /**
     * @param DuelProfile $winner
     */
    public function roundsEnded(DuelProfile $winner): void {
        foreach ($this->getEveryone() as $duelProfile) {
        }
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