<?php

declare(strict_types=1);

namespace bitrule\practice\listener\entity;

use bitrule\practice\registry\ProfileRegistry;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;

final class EntityMotionListener implements Listener {

    /**
     * @param EntityMotionEvent $ev
     */
    public function onEntityMotionEvent(EntityMotionEvent $ev): void {
        $entity = $ev->getEntity();
        if (!$entity instanceof Player) return;

        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($entity->getXuid());
        if ($localProfile === null) return;

        if ($localProfile->initialKnockbackMotion) {
            $localProfile->initialKnockbackMotion = false;
            $localProfile->cancelKnockbackMotion = true;
        } elseif ($localProfile->cancelKnockbackMotion) {
            $localProfile->cancelKnockbackMotion = false;
            $ev->cancel();
        }
    }
}