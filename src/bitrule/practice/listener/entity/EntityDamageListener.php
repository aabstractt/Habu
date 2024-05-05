<?php

declare(strict_types=1);

namespace bitrule\practice\listener\entity;

use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

final class EntityDamageListener implements Listener {

    /**
     * @param EntityDamageEvent $ev
     */
    public function onEntityDamageEvent(EntityDamageEvent $ev): void {
        $entity = $ev->getEntity();
        if (!$entity instanceof Player) return;

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($entity->getXuid());
        if ($duel === null) return;

        $stage = $duel->getStage();
        if (!$stage instanceof PlayingStage) return;

        if ($ev instanceof EntityDamageByEntityEvent) {
            $stage->onEntityDamageByEntityEvent($duel, $entity, $ev);
        } else {
            $stage->onAnythingDamageEvent($duel, $entity, $ev);
        }
    }
}