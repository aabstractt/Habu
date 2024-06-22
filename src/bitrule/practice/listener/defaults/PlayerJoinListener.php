<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\TranslationKey;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Server;
use function count;

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

        $player->sendMessage(TranslationKey::PLAYER_WELCOME_MESSAGE()->build(
            $player->getName(),
            count(Server::getInstance()->getOnlinePlayers())
        ));

        DuelRegistry::getInstance()->setPlayerObject($player);

        ProfileRegistry::getInstance()->addProfile($player);
    }
}