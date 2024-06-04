<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\profile\Profile;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

abstract class KillablePlayingStage extends PlayingStage implements AnythingDamageStageListener, AttackDamageStageListener {

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
        if ($victim->getHealth() - $ev->getFinalDamage() > 0) return;

        Profile::resetInventory($victim);
        $victim->setGamemode(GameMode::SPECTATOR);

        $this->killPlayer(
            $duel,
            $victim,
            $ev instanceof EntityDamageByEntityEvent ? $ev->getDamager() : null,
            $ev->getCause()
        );

        $ev->cancel();
    }

    /**
     * @param Duel        $duel
     * @param Player      $victim
     * @param Entity|null $attacker
     * @param int         $cause
     */
    abstract public function killPlayer(Duel $duel, Player $victim, ?Entity $attacker, int $cause): void;
}