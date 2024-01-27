<?php

declare(strict_types=1);

namespace bitrule\practice\match\stage;

use bitrule\practice\match\AbstractMatch;

final class EndingStage implements AbstractStage {

    /** @var int */
    private int $countdown = 5;

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param AbstractMatch $match
     */
    public function update(AbstractMatch $match): void {
        $this->countdown--;

        if ($this->countdown > 1) return;

        $match->postEnd();
    }
}