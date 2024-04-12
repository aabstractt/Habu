<?php

declare(strict_types=1);

namespace bitrule\practice\listener\match;

use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\manager\DuelManager;
use bitrule\practice\manager\ProfileManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use RuntimeException;

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

        $match = DuelManager::getInstance()->getDuelByPlayer($player->getXuid());
        if ($match === null || !$match->getStage() instanceof PlayingStage) return;
        // TODO: Kill player

        $duelProfile = ProfileManager::getInstance()->getDuelProfile($player->getXuid());
        if ($duelProfile === null) {
            throw new RuntimeException('Error code 2');
        }

        $duelProfile->convertAsSpectator($match, false);
    }
}