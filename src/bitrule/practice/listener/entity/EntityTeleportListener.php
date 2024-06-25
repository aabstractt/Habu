<?php

declare(strict_types=1);

namespace bitrule\practice\listener\entity;

use bitrule\habu\ffa\HabuFFA;
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
        $source = $ev->getEntity();
        if (!$source instanceof Player || !$source->isOnline()) return;

        $to = $ev->getTo();
        $from = $ev->getFrom();
        if ($to->getWorld() === $from->getWorld()) return;

        SumoEvent::getInstance()->listenPlayerMove($source, $ev->getTo());

        HabuFFA::getInstance()->quitByWorld($source, $from->getWorld()->getFolderName());

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($source->getXuid());
        if ($duel === null) return;

        $to = $ev->getTo();
        if ($to->getWorld() === $duel->getWorld()) return;

        DuelRegistry::getInstance()->quitPlayer($source);
    }
}