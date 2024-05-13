<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\TranslationKey;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;

final class PlayerJoinListener implements Listener {

    /**
     * @param PlayerJoinEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerJoinEvent(PlayerJoinEvent $ev): void {
        $player = $ev->getPlayer();

        $ev->setJoinMessage(TranslationKey::PLAYER_JOINED_MESSAGE()->build($player->getName()));

        // Prevent handle event if the player not is online
        if (!$player->isOnline()) return;

        ProfileRegistry::getInstance()->addLocalProfile($player);
    }
}