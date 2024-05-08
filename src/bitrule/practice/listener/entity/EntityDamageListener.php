<?php

declare(strict_types=1);

namespace bitrule\practice\listener\entity;

use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KnockbackRegistry;
use bitrule\practice\registry\ProfileRegistry;
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

        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($entity->getXuid());
        if ($localProfile === null) {
            throw new \RuntimeException('LocalProfile is null');
        }

        if ($ev instanceof EntityDamageByEntityEvent) {
            if ($ev->isCancelled()) return;

            if ($ev->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0) {
                $ev->cancel();

                return;
            }

            $knockbackProfile = KnockbackRegistry::getInstance()->getKnockback($localProfile->getKnockbackProfile());
            if ($knockbackProfile === null) {
                throw new \RuntimeException('KnockbackProfile for ' . $localProfile->getKnockbackProfile() . ' is null');
            }

            $ev->setKnockBack(0.0);

            if ($knockbackProfile->getHitDelay() > 0) {
                $ev->setAttackCooldown($knockbackProfile->getHitDelay());
            }

            $knockbackProfile->applyOn($entity, $localProfile, $ev->getDamager());
        }

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($entity->getXuid());
        if ($duel === null) return;

        $stage = $duel->getStage();
        if (!$stage instanceof PlayingStage) {
            $ev->cancel();

            return;
        }

        if ($ev instanceof EntityDamageByEntityEvent) {
            $stage->onEntityDamageByEntityEvent($duel, $entity, $ev);
        } else {
            $stage->onAnythingDamageEvent($duel, $entity, $ev);
        }
    }
}