<?php

declare(strict_types=1);

namespace bitrule\practice\listener\match;

use bitrule\practice\arena\impl\FireballFightStage;
use bitrule\practice\duel\properties\FireballFightProperties;
use bitrule\practice\event\duel\DuelStartedEvent;
use pocketmine\event\Listener;

final class DuelStartedListener implements Listener {

    /**
     * @param DuelStartedEvent $ev
     *
     * @priority NORMAL
     */
    public function onDuelStartedEvent(DuelStartedEvent $ev): void {
        $duel = $ev->getDuel();
        if ($duel->getArena() instanceof FireballFightStage) {
            $duel->setProperties(new FireballFightProperties());
        }
    }
}