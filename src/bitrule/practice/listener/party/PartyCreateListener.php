<?php

declare(strict_types=1);

namespace bitrule\practice\listener\party;

use bitrule\parties\event\PartyCreateEvent;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\Listener;

final class PartyCreateListener implements Listener {

    /**
     * @param PartyCreateEvent $ev
     *
     * @priority NORMAL
     */
    public function onPartyCreateEvent(PartyCreateEvent $ev): void {
        $ownership = $ev->getOwnership();
        if (!$ownership->isOnline()) return;

        if (DuelRegistry::getInstance()->getDuelByPlayer($ownership->getXuid()) !== null) return;

        Profile::setDefaultAttributes($ownership);
    }
}