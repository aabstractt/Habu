<?php

declare(strict_types=1);

namespace bitrule\practice\listener\entity;

use bitrule\practice\duel\events\SumoEvent;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

final class EntityTeleportListener implements Listener {

    /**
     * @param EntityTeleportEvent $ev
     *
     * @priority NORMAL
     */
    public function onEntityTeleportEvent(EntityTeleportEvent $ev): void {
        $entity = $ev->getEntity();
        if (!$entity instanceof Player || !$entity->isOnline()) return;

        $to = $ev->getTo();
        $from = $ev->getFrom();
        if ($to->getWorld() === $from->getWorld()) return;

        $sumoEvent = SumoEvent::getInstance();
        if ($sumoEvent->isPlaying($entity) && !$sumoEvent->isVectorInside($ev->getTo(), false)) {
            $sumoEvent->quitPlayer($entity, false);
        }

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($entity->getXuid());
        if ($duel === null) return;

        $to = $ev->getTo();
        if ($to->getWorld() === $duel->getWorld()) return;

        DuelRegistry::getInstance()->quitPlayer($entity);
    }
}