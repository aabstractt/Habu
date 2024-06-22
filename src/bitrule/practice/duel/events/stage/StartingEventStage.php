<?php

declare(strict_types=1);

namespace bitrule\practice\duel\events\stage;

use bitrule\practice\duel\events\SumoEvent;

final class StartingEventStage implements EventStage {

    /**
     * @param int $countdown
     */
    public function __construct(private int $countdown) {}

    /**
     * @param SumoEvent $event
     */
    public function update(SumoEvent $event): void {
        $this->countdown--;

        if ($this->countdown > 0) return;

        $event->setStage($stage = new StartedEventStage());
        $stage->end($event);
    }
}