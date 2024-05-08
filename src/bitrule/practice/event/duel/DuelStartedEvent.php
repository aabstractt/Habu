<?php

declare(strict_types=1);

namespace bitrule\practice\event\duel;

use bitrule\practice\duel\Duel;
use pocketmine\event\Event;

final class DuelStartedEvent extends Event {

    /**
     * @param Duel $duel
     */
    public function __construct(private readonly Duel $duel) {}

    /**
     * @return Duel
     */
    public function getDuel(): Duel {
        return $this->duel;
    }
}