<?php

declare(strict_types=1);

namespace bitrule\practice\listener\match;

use bitrule\practice\manager\MatchManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;

final class SumoPlayerMoveListener implements Listener {

    /** @var int */
    final public const MIN_Y = 7;

    /**
     * @param PlayerMoveEvent $ev
     */
    public function onPlayerMoveEvent(PlayerMoveEvent $ev): void {
        $player = $ev->getPlayer();
        if (!$player->isOnline()) return;
        if ($player->getLocation()->getFloorY() > self::MIN_Y) return;

        $match = MatchManager::getInstance()->getMatchByPlayer($player->getXuid());
        if ($match === null) return;
        // TODO: Kill player
    }
}