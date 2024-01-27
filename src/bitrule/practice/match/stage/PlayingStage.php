<?php

declare(strict_types=1);

namespace bitrule\practice\match\stage;

use bitrule\practice\match\AbstractMatch;
use function count;

final class PlayingStage implements AbstractStage {

    /** @var int */
    private int $seconds = 0;

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param AbstractMatch $match
     */
    public function update(AbstractMatch $match): void {
        if (!$match->isLoaded()) {
            throw new \RuntimeException('Match is not loaded.');
        }

        if (count($match->getAlive()) > 0) {
            $this->seconds++;

            return;
        }

        $match->setStage(new EndingStage());
        $match->end();
    }

    /**
     * @return int
     */
    public function getSeconds(): int {
        return $this->seconds;
    }
}