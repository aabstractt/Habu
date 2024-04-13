<?php

declare(strict_types=1);

namespace bitrule\practice\listener;

use bitrule\practice\event\MatchEndEvent;
use pocketmine\event\Listener;
use pocketmine\Server;

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
    }
}