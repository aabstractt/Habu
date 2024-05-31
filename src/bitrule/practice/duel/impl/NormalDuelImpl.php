<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelMember;
use bitrule\practice\duel\impl\trait\OpponentDuelTrait;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\Habu;
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
     * @param Player     $player
     * @param DuelMember $duelMember
     */
    public function processPlayerPrepare(Player $player, DuelMember $duelMember): void {
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

        Habu::applyScoreboard($player, ProfileRegistry::MATCH_STARTING_SCOREBOARD);
    }

    /**
     * Called when the duel stage changes
     * to Ending.
     */
    public function end(): void {
        if ($this->ending) return;

        parent::end();

        $duelMember = $this->getWinner();
        if ($duelMember === null) return;

        $player = $duelMember->toPlayer();
        if ($player === null || !$player->isOnline()) return;

        $opponent = $this->getOpponent($player);
        if ($opponent === null) return;

        Server::getInstance()->broadcastMessage(TranslationKey::DUEL_WINNER_BROADCAST()->build(
            $player->getName(),
            $opponent->getName(),
            $this->kit->getName()
        ));

        [$winElo, $lostElo] = $this->ranked ? DuelRegistry::calculateElo(
            $duelMember->getElo(),
            $opponent->getElo()
        ) : [0, 0];

        $selfDuelStatistics = $duelMember->getDuelStatistics();
        $opponentDuelStatistics = $opponent->getDuelStatistics();

        $player->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
            $opponent->getName(),
            $winElo > 0 ? TranslationKey::DUEL_ELO_CHANGES_WIN()->build((string) $winElo) : TextFormat::YELLOW . 'No changes',
            (string) $selfDuelStatistics->getCritics(),
            (string) $selfDuelStatistics->getDamageDealt(),
            (string) $opponentDuelStatistics->getCritics(),
            (string) $opponentDuelStatistics->getDamageDealt(),
        ));

        $opponentPlayer = $opponent->toPlayer();
        if ($opponentPlayer === null || !$opponentPlayer->isOnline()) return;

        $opponentPlayer->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
            $duelMember->getName(),
            $lostElo > 0 ? TranslationKey::DUEL_ELO_CHANGES_LOST()->build((string) $lostElo) : TextFormat::YELLOW . 'No changes',
            (string) $opponentDuelStatistics->getCritics(),
            (string) $opponentDuelStatistics->getDamageDealt(),
            (string) $selfDuelStatistics->getCritics(),
            (string) $selfDuelStatistics->getDamageDealt(),
        ));

        $xuids = [$player->getXuid(), $opponentPlayer->getXuid()];
        foreach ($xuids as $id => $xuid) {
            $profile = ProfileRegistry::getInstance()->getProfile($xuid);
            if ($profile === null) continue;

            $profile->setElo($id === 0 ? $winElo : $lostElo);
        }
    }

    /**
     * Remove a player from the match.
     * Check if the match can end.
     * Usually is checked when the player died or left the match.
     *
     * @param Player $player
     */
    public function removePlayer(Player $player): void {
        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) {
            throw new RuntimeException('Player not found in the match.');
        }

        unset($this->playersSpawn[$player->getXuid()]);

        if ($this->ending) return;

        $duelMember = $this->getMember($player->getXuid());
        if ($duelMember === null) {
            throw new RuntimeException('Player not found in the match.');
        }

        if ($duelMember->isAlive()) {
            $duelMember->convertAsSpectator($this, false);
        }

//        $expectedPlayersAlive = $spawnId > 2 ? 1 : 2;
        if (count($this->getAlive()) > 2) return;

        $this->end();
    }
}