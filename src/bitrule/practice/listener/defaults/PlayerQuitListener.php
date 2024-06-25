<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use bitrule\habu\ffa\HabuFFA;
use bitrule\practice\registry\DuelRegistry;
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

        HabuFFA::getInstance()->quitByWorld($player, $player->getWorld()->getFolderName());

        DuelRegistry::getInstance()->quitPlayer($player);
        DuelRegistry::getInstance()->disconnectPlayer($player->getXuid());
        ProfileRegistry::getInstance()->quitPlayer($player);
    }
}