<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
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

final class NormalDuelImpl extends Duel {
    use SpectatingDuelTrait;
    use OpponentDuelTrait;

    /**
     * Called when the duel stage changes
     * to Ending.
     */
    public function end(): void {
        parent::end();

        $duelProfile = $this->getWinner();
        if ($duelProfile === null) return;

        $player = $duelProfile->toPlayer();
        if ($player === null || !$player->isOnline()) return;

        $opponent = $this->getOpponent($player);
        if ($opponent === null) return;

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
        if ($opponentPlayer === null || !$opponentPlayer->isOnline()) return;

        $opponentPlayer->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
            $duelProfile->getName(),
            $lostElo > 0 ? TranslationKey::DUEL_ELO_CHANGES_LOST()->build((string) $lostElo) : TextFormat::YELLOW . 'No changes',
            (string) $opponentMatchStatistics->getCritics(),
            (string) $opponentMatchStatistics->getDamageDealt(),
            (string) $matchStatistics->getCritics(),
            (string) $matchStatistics->getDamageDealt(),
        ));

        // Apply elo changes to the duelProfile
        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) return;

        $localProfile->setElo($winElo);

        // Apply elo changes to the loser
        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($opponentPlayer->getXuid());
        if ($localProfile === null) return;

        $localProfile->setElo($lostElo);
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

        unset($this->playersSpawn[$player->getXuid()]);

        $duelPlayer = $this->getPlayer($player->getXuid());
        if ($duelPlayer === null) {
            throw new RuntimeException('Player not found in the match.');
        }

        if ($duelPlayer->isAlive() && !$this->ending) {
            $duelPlayer->convertAsSpectator($this, false);
        }

        if (!$canEnd) return;

        $expectedPlayersAlive = $spawnId > 2 ? 1 : 2;
        if (count($this->getAlive()) > $expectedPlayersAlive) return;

        $this->end();
    }
}
