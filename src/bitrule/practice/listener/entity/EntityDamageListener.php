<?php

declare(strict_types=1);

namespace bitrule\practice\listener\entity;

use bitrule\practice\duel\stage\impl\AnythingDamageStageListener;
use bitrule\practice\duel\stage\impl\AttackDamageStageListener;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KnockbackRegistry;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use RuntimeException;

final class EntityDamageListener implements Listener {

    /**
     * @param EntityDamageEvent $ev
     *
     * @priority NORMAL
     */
    public function onEntityDamageEvent(EntityDamageEvent $ev): void {
        $victim = $ev->getEntity();
        if (!$victim instanceof Player) return;

        $profile = ProfileRegistry::getInstance()->getProfile($victim->getXuid());
        if ($profile === null) {
            throw new RuntimeException('Profile is null');
        }

        if ($ev instanceof EntityDamageByEntityEvent) {
            if ($ev->isCancelled()) return;

            if ($ev->getModifier(EntityDamageEvent::MODIFIER_PREVIOUS_DAMAGE_COOLDOWN) < 0) {
                $ev->cancel();

                return;
            }

            $knockbackProfile = KnockbackRegistry::getInstance()->getKnockback($profile->getKnockbackProfile());
            if ($knockbackProfile === null) {
                throw new RuntimeException('KnockbackProfile for ' . $profile->getKnockbackProfile() . ' is null');
            }

            $ev->setKnockBack(0.0);

            if ($knockbackProfile->getHitDelay() > 0) {
                $ev->setAttackCooldown($knockbackProfile->getHitDelay());
            }

            $knockbackProfile->applyOn($victim, $profile, $ev->getDamager());
        }

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($victim->getXuid());
        if ($duel === null) return;

        $stage = $duel->getStage();
        if (!$stage instanceof PlayingStage) {
            $ev->cancel();

            return;
        }

        if (!$ev instanceof EntityDamageByEntityEvent && $stage instanceof AnythingDamageStageListener) {
            $stage->onAnythingDamageEvent($duel, $victim, $ev);

            return;
        }

        if (!$ev instanceof EntityDamageByEntityEvent) return;

        $victimProfile = $duel->getMember($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $attacker = $ev->getDamager();
        if (!$attacker instanceof Player) return;

        $attackerProfile = $duel->getMember($attacker->getXuid());
        if ($attackerProfile === null || !$attackerProfile->isAlive()) return;

        $attackerDuelStatistics = $attackerProfile->getDuelStatistics();
        $attackerDuelStatistics->increaseDamageDealt($ev->getFinalDamage());
        $attackerDuelStatistics->increaseTotalHits();

        if ($stage instanceof AttackDamageStageListener) {
            $stage->onEntityDamageByEntityEvent($duel, $victim, $ev);
        }
    }
}