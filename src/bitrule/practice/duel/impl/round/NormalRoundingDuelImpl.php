<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\round;

use bitrule\practice\duel\impl\trait\OpponentDuelTrait;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\TranslationKey;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function count;

final class NormalRoundingDuelImpl extends RoundingDuel {
    use SpectatingDuelTrait;
    use OpponentDuelTrait;

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

            Server::getInstance()->broadcastMessage(TranslationKey::DUEL_WINNER_BROADCAST()->build(
                $player->getName(),
                $opponent->getName(),
                $this->kit->getName()
            ));

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
}