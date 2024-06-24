<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\duel\Duel;
use bitrule\practice\Habu;
use bitrule\scoreboard\ScoreboardRegistry;
use function is_int;

final class EndingStage implements AbstractStage {

    /**
     * @param int $countdown
     * @param int $duration
     */
    private function __construct(
        private int $countdown,
        private readonly int $duration
    ) {}

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param Duel $duel
     */
    public function update(Duel $duel): void {
        if (!$duel->isLoaded()) return;

        $this->countdown--;

        if ($this->countdown === 4) {
            /**
             * Send again the scoreboard to the players.
             * This is necessary because the scoreboard is removed when the match ends.
             * This is a workaround to fix the issue.
             * {@see Duel::end()}
             */
            foreach ($duel->getEveryone() as $duelMember) {
                $player = $duelMember->toPlayer();
                if ($player === null || !$player->isOnline()) continue;

                ScoreboardRegistry::getInstance()->apply($player, Habu::MATCH_ENDING_SCOREBOARD);
            }
        }

        if ($this->countdown > 1) return;

        $duel->postEnd();
    }

    /**
     * @return int
     */
    public function getDuration(): int {
        return $this->duration;
    }

    /**
     * @param int $duration
     *
     * @return self
     */
    public static function create(int $duration): self {
        return new self(
            is_int($value = Habu::getInstance()->getConfig()->get('duel.end-countdown', 5)) ? $value : 5,
            $duration
        );
    }
}