<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use bitrule\practice\manager\MatchManager;
use bitrule\practice\manager\ProfileManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;

final class PlayerQuitListener implements Listener {

    /**
     * Handle player quit event to remove player from match
     *
     * @param PlayerQuitEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerQuitEvent(PlayerQuitEvent $ev): void {
        $player = $ev->getPlayer();

        $match = MatchManager::getInstance()->getMatchByPlayer($player->getXuid());
        $match?->removePlayer($player, true);

        ProfileManager::getInstance()->removeProfile($player->getXuid());
    }
}