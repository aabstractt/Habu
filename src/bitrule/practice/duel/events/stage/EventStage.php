<?php

declare(strict_types=1);

namespace bitrule\practice\duel\events\stage;

use bitrule\practice\duel\events\SumoEvent;

interface EventStage {

    /**
     * @param SumoEvent $event
     */
    public function update(SumoEvent $event): void;
}