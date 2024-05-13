<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\stage\StartingStage;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\TranslationKey;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function abs;
use function count;
use function str_starts_with;

final class NormalDuelImpl extends Duel {
    use SpectatingDuelTrait;

    /**
     * Called when the duel stage changes
     * to Ending.
     */
    public function end(): void {
        foreach ($this->getPlayers() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            $opponent = $this->getOpponent($player);
            if ($opponent === null) continue;

            [$winElo, $lostElo] = $this->ranked ? DuelRegistry::calculateElo(
                $duelProfile->getElo(),
                $opponent->getElo()
            ) : [0, 0];

            $matchStatistics = $duelProfile->getDuelStatistics();
            $opponentMatchStatistics = $opponent->getDuelStatistics();

            $player->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
                $opponent->getName(),
                $winElo > 0 ? TranslationKey::DUEL_ELO_CHANGES_WIN()->build((string) $winElo) : TextFormat::YELLOW . 'No changes',
                (string) $matchStatistics->getCritics(),
                (string) $matchStatistics->getDamageDealt(),
                (string) $opponentMatchStatistics->getCritics(),
                (string) $opponentMatchStatistics->getDamageDealt(),
            ));

            $opponentPlayer = $opponent->toPlayer();
            if ($opponentPlayer === null || !$opponentPlayer->isOnline()) continue;

            $opponentPlayer->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
                $duelProfile->getName(),
                $lostElo > 0 ? TranslationKey::DUEL_ELO_CHANGES_LOST()->build((string) $lostElo) : TextFormat::YELLOW . 'No changes',
                (string) $opponentMatchStatistics->getCritics(),
                (string) $opponentMatchStatistics->getDamageDealt(),
                (string) $matchStatistics->getCritics(),
                (string) $matchStatistics->getDamageDealt(),
            ));

            // Apply elo changes to the winner
            $localProfile = ProfileRegistry::getInstance()->getLocalProfile($player->getXuid());
            if ($localProfile === null) continue;

            $localProfile->setElo($winElo);

            // Apply elo changes to the loser
            $localProfile = ProfileRegistry::getInstance()->getLocalProfile($opponentPlayer->getXuid());
            if ($localProfile === null) continue;

            $localProfile->setElo($lostElo);
        }

        parent::end();
    }

    /**
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerPrepare(Player $player, DuelProfile $duelProfile): void {
        $this->playersSpawn[$player->getXuid()] = count($this->playersSpawn);

        $opponentName = $this->getOpponentName($player->getXuid());
        if ($opponentName === null) {
            throw new RuntimeException('Opponent not found.');
        }

        $player->sendMessage(TranslationKey::DUEL_OPPONENT_FOUND()->build(
            $opponentName,
            $this->ranked ? 'Ranked' : 'Unranked',
            $this->kit->getName()
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
        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) {
            throw new RuntimeException('Player not found in the match.');
        }

        // TODO: This going to give some issues
        // TODO: Already fixed
        unset($this->playersSpawn[$player->getXuid()]);

        $duelPlayer = $this->getPlayer($player->getXuid());
        if ($duelPlayer === null) {
            throw new RuntimeException('Player not found in the match.');
        }

        if ($duelPlayer->isAlive()) {
            $duelPlayer->convertAsSpectator($this, false);
        }

        if (!$canEnd) return;

        $expectedPlayersAlive = $spawnId > 2 ? 1 : 2;
        if (count($this->getAlive()) > $expectedPlayersAlive) return;

        $this->end();
    }

    /**
     * TODO: Move this to an trait
     *
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

        $duelProfile = $this->getPlayer($player->getXuid());
        if ($duelProfile === null) return null;

        if (str_starts_with($identifier, 'duel-opponent')) {
            $opponent = $this->getOpponent($player);
            if ($opponent === null) return null;

            $instance = $opponent->toPlayer();
            if ($instance === null || !$instance->isOnline()) return null;

            return $identifier === 'duel-opponent-name' ? $opponent->getName() : (string) $instance->getNetworkSession()->getPing();
        }

        return null;
    }
}
