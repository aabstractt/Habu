<?php

declare(strict_types=1);

namespace bitrule\practice\arena\listener;

use bitrule\practice\duel\Duel;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

interface AnythingDamageArenaListener {

    /**
     * This method is called when a player is damaged by anything
     * except another player.
     *
     * @param Duel              $duel
     * @param Player            $victim
     * @param EntityDamageEvent $ev
     */
    public function onAnythingDamageEvent(Duel $duel, Player $victim, EntityDamageEvent $ev): void;
}