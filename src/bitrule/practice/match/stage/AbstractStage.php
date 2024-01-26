<?php

declare(strict_types=1);

namespace bitrule\practice\match\stage;

use bitrule\practice\match\AbstractMatch;

abstract class AbstractStage {

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param AbstractMatch $match
     */
    abstract public function update(AbstractMatch $match): void;
}