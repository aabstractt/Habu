<?php

declare(strict_types=1);

namespace bitrule\practice\match\stage;

use bitrule\practice\match\AbstractMatch;

interface AbstractStage {

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param AbstractMatch $match
     */
    public function update(AbstractMatch $match): void;
}