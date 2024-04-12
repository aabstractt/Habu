<?php

declare(strict_types=1);

namespace bitrule\practice\listener;

use bitrule\practice\event\MatchEndEvent;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_filter;
use function array_map;

final class MatchEndListener implements Listener {

    /**
     * @param MatchEndEvent $ev
     */
    public function onMatchEndEvent(MatchEndEvent $ev): void {
        $match = $ev->getMatch();
        $matchRounds = $match->getMatchRounds();

        if ($matchRounds === null) {
            echo 'NO ROUNDS';

            return;
        }

        $winner = $ev->getWinner()?->getXuid();
        if ($winner !== null) {
            $matchRounds->setWinner($winner);
        }

        if (!$ev->hasSomeoneDisconnected()) {
            $winner = $matchRounds->getWinner();
        }

        if ($winner !== null) {
            Server::getInstance()->broadcastMessage('Player ' . $winner . ' won!');

            return;
        }

        if ($ev->hasSomeoneDisconnected()) return;

        $matchRounds->setCurrentRound($matchRounds->getCurrentRound() + 1);

        DuelRegistry::getInstance()->createMatchForRounding(
            array_filter(
                array_map(fn(DuelProfile $duelProfile) => $duelProfile->toPlayer(), $ev->getPlayers()),
                fn(?Player $player) => $player !== null
            ),
            array_filter(
                array_map(fn(DuelProfile $duelProfile) => $duelProfile->toPlayer(), $ev->getSpectators()),
                fn(?Player $player) => $player !== null
            ),
            $match->getKit(),
            $match->getArena(),
            $matchRounds,
            $match->isRanked()
        );
    }
}