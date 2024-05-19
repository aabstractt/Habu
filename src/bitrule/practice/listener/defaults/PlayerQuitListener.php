<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use bitrule\practice\Practice;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\TranslationKey;
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

        $ev->setQuitMessage(TranslationKey::PLAYER_LEFT_MESSAGE()->build($player->getName()));

        ProfileRegistry::getInstance()->quitPlayer($player);

        // Listen to the event when a player quits
        Practice::getInstance()->getPartyAdapter()?->onPlayerQuit($player);
    }
}