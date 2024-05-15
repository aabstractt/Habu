<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\TranslationKey;
use LogicException;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

final class DefaultPlayingStage extends PlayingStage implements AnythingDamageStageListener, AttackDamageStageListener {

    /**
     * This method is called when a player is damaged by another player.
     *
     * @param Duel                      $duel
     * @param Player                    $victim
     * @param EntityDamageByEntityEvent $ev
     */
    public function onEntityDamageByEntityEvent(Duel $duel, Player $victim, EntityDamageByEntityEvent $ev): void {
        $this->onAnythingDamageEvent($duel, $victim, $ev);
    }

    /**
     * This method is called when a player is damaged by anything
     * except another player.
     *
     * @param Duel              $duel
     * @param Player            $victim
     * @param EntityDamageEvent $ev
     */
    public function onAnythingDamageEvent(Duel $duel, Player $victim, EntityDamageEvent $ev): void {
        $victimProfile = $duel->getMember($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) {
            $ev->cancel();

            return;
        }

        if ($victim->getHealth() - $ev->getFinalDamage() > 0) return;

        $ev->cancel();

        $victim->setHealth($victim->getMaxHealth());

        $attacker = $ev instanceof EntityDamageByEntityEvent ? $ev->getDamager() : null;

        $attackerProfile = $attacker instanceof Player ? $duel->getMember($attacker->getXuid()) : null;
        if ($attacker !== null && ($attackerProfile === null || !$attackerProfile->isAlive())) {
            $ev->cancel();

            return;
        }

        $victimSpawnId = $duel->getSpawnId($victim->getXuid());
        if ($victimSpawnId > 1) {
            throw new LogicException('Invalid spawn id: ' . $victimSpawnId);
        }

        if ($attackerProfile === null || $attacker === null) {
            $duel->broadcastMessage(TranslationKey::DUEL_PLAYER_DEAD_WITHOUT_KILLER()->build(
                $victim->getName()
            ));
        } else {
            $duel->broadcastMessage(TranslationKey::DUEL_PLAYER_DEAD()->build(
                $victim->getName(),
                $attackerProfile->getName()
            ));

            $attackerProfile->getDuelStatistics()->setKills($attackerProfile->getDuelStatistics()->getKills() + 1);
        }

        $victimProfile->convertAsSpectator($duel, false);
    }
}