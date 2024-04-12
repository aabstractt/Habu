<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\duel\Duel;

final class PlayingStage implements AbstractStage {

    /** @var int */
    private int $seconds = 0;

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param Duel $duel
     */
    public function update(Duel $duel): void {
        if (!$duel->isLoaded()) {
            throw new \RuntimeException('Match is not loaded.');
        }

        $this->seconds++;
    }

    /**
     * @return int
     */
    public function getSeconds(): int {
        return $this->seconds;
    }
}