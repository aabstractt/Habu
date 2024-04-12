<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\duel\Duel;
use bitrule\practice\Practice;
use bitrule\practice\registry\ProfileRegistry;
use function count;

final class StartingStage implements AbstractStage {

    /** @var int */
    private int $countdown = 5;

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param Duel $duel
     */
    public function update(Duel $duel): void {
        if (!$duel->isLoaded()) return;

        if (count($duel->getAlive()) < 2) {
            $duel->end();

            return;
        }

        $this->countdown--;

        $duel->broadcastMessage('Match starting in ' . $this->countdown . ' seconds.');

        if ($this->countdown > 1) return;

        $duel->setStage(new PlayingStage());

        foreach ($duel->getEveryone() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            Practice::setProfileScoreboard($player, ProfileRegistry::MATCH_PLAYING_SCOREBOARD);
        }
    }

    /**
     * @return int
     */
    public function getCountdown(): int {
        return $this->countdown;
    }
}