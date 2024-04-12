<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\duel\Duel;

interface AbstractStage {

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param Duel $duel
     */
    public function update(Duel $duel): void;
}