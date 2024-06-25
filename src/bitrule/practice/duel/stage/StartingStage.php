<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\PartyFFADuelImpl;
use bitrule\practice\event\duel\DuelStartedEvent;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\TranslationKey;
use bitrule\scoreboard\ScoreboardRegistry;
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

        $duel->broadcastMessage(TranslationKey::DUEL_STARTING_COUNTDOWN()->build($this->countdown));

        if ($this->countdown > 1) return;

        $duel->setStage($stage = PlayingStage::create($duel->getArenaProperties()));

        (new DuelStartedEvent($duel))->call();

        $scoreboardId = $stage instanceof StageScoreboard ? $stage->getScoreboardId($duel) : ProfileRegistry::MATCH_PLAYING_SCOREBOARD;
        if ($duel instanceof PartyFFADuelImpl) $scoreboardId .= '-party';

        foreach ($duel->getEveryone() as $duelMember) {
            $player = $duelMember->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            ScoreboardRegistry::getInstance()->apply($player, $scoreboardId);
        }
    }

    /**
     * @return int
     */
    public function getCountdown(): int {
        return $this->countdown;
    }
}