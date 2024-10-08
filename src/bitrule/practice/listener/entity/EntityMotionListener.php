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

        $profile = ProfileRegistry::getInstance()->getProfile($entity->getXuid());
        if ($profile === null) return;

        $motion = $profile->motion;
        if ($motion === null) return;

        $ev->cancel();

        $profile->motion = null;
        $entity->setMotion($motion);

//        if ($profile->initialKnockbackMotion) {
//            $profile->initialKnockbackMotion = false;
//            $profile->cancelKnockbackMotion = true;
//        } elseif ($profile->cancelKnockbackMotion) {
//            $profile->cancelKnockbackMotion = false;
//            $ev->cancel();
//        }
    }
}