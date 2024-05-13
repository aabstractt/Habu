<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\player\Player;
use RuntimeException;

final class PlayerExhaustListener implements Listener {

    /**
     * @param PlayerExhaustEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerExhaustEvent(PlayerExhaustEvent $ev): void {
        $player = $ev->getPlayer();
        if (!$player instanceof Player) return;

        if (!$player->isOnline()) {
            throw new RuntimeException('Player is not online');
        }

        $ev->cancel();

        $hungerManager = $player->getHungerManager();
        if ($hungerManager->getFood() === $hungerManager->getMaxFood()) return;

        $hungerManager->setFood($hungerManager->getMaxFood());
    }
}