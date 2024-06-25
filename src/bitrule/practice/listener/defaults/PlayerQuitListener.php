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

        /**
         * Everything here was tryhardcoded so this is a shit
         * This is an old version, so after give this I'm going to start the development of Habu v2
         * for a better performance and code cleanup
         */

        $ev->setQuitMessage(TranslationKey::PLAYER_LEFT_MESSAGE()->build($player->getName()));

        HabuFFA::getInstance()->killPlayer($player, $player->getWorld());
        HabuFFA::getInstance()->quitByWorld($player);

        DuelRegistry::getInstance()->quitPlayer($player);
        DuelRegistry::getInstance()->disconnectPlayer($player->getXuid());
        ProfileRegistry::getInstance()->quitPlayer($player);

        DuelRegistry::getInstance()->clearDuelInvites($player->getXuid());
    }
}