<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\duel\Duel;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;

interface AttackDamageStageListener {

    /**
     * This method is called when a player is damaged by another player.
     *
     * @param Duel                      $duel
     * @param Player                    $victim
     * @param EntityDamageByEntityEvent $ev
     */
    public function onEntityDamageByEntityEvent(Duel $duel, Player $victim, EntityDamageByEntityEvent $ev): void;
}