<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\duel\Duel;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\match\AbstractMatch;

final class EndingStage implements AbstractStage {

    /**
     * @param int $countdown
     * @param int $duration
     */
    public function __construct(
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

        if ($this->countdown > 1) return;

        $duel->postEnd();

        DuelRegistry::getInstance()->endMatch($duel);
    }

    /**
     * @return int
     */
    public function getDuration(): int {
        return $this->duration;
    }
}