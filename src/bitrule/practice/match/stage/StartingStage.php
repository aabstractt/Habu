<?php

declare(strict_types=1);

namespace bitrule\practice\match\stage;

use bitrule\practice\manager\ProfileManager;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\Practice;

final class StartingStage implements AbstractStage {

    /** @var int */
    private int $countdown = 5;

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param AbstractMatch $match
     */
    public function update(AbstractMatch $match): void {
        if (!$match->isLoaded()) return;

        $this->countdown--;

        $match->broadcastMessage('Match starting in ' . $this->countdown . ' seconds.');

        if ($this->countdown > 1) return;

        $match->setStage(new PlayingStage());

        foreach ($match->getEveryone() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            Practice::setProfileScoreboard($player, ProfileManager::MATCH_PLAYING_SCOREBOARD);
        }
    }

    /**
     * @return int
     */
    public function getCountdown(): int {
        return $this->countdown;
    }
}