<?php

declare(strict_types=1);

namespace bitrule\practice\match\stage;

use bitrule\practice\match\AbstractMatch;

final class EndingStage implements AbstractStage {

    public function __construct(
        private int $countdown,
        private int $duration
    ) {}

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param AbstractMatch $match
     */
    public function update(AbstractMatch $match): void {
        if (!$match->isLoaded()) return;

        $this->countdown--;

        if ($this->countdown > 1) return;

        $match->postEnd();
    }

    /**
     * @return int
     */
    public function getDuration(): int {
        return $this->duration;
    }
}