<?php

declare(strict_types=1);

namespace bitrule\practice\listener;

use bitrule\practice\manager\DuelManager;
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

        $match = DuelManager::getInstance()->getDuelByPlayer($entity->getXuid());
        if ($match === null) return;

        $to = $ev->getTo();
        if ($to->getWorld() === $match->getWorld()) return;

        $match->removePlayer($entity, true);
    }
}