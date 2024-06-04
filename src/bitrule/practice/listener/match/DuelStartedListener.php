<?php

declare(strict_types=1);

namespace bitrule\practice\listener\match;

use bitrule\practice\event\duel\DuelStartedEvent;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\event\Listener;

final class DuelStartedListener implements Listener {

    /**
     * @param DuelStartedEvent $ev
     */
    public function onDuelStartedEvent(DuelStartedEvent $ev): void {
        $selectedKnockback = $ev->getDuel()->getSelectedKnockback() ?? $ev->getDuel()->getKit()->getKnockbackProfile();
        foreach ($ev->getDuel()->getPlaying() as $duelMember) {
            $profile = ProfileRegistry::getInstance()->getProfile($duelMember->getXuid());
            if ($profile === null) continue;

            $profile->setKnockbackProfile($selectedKnockback);
        }
    }
}