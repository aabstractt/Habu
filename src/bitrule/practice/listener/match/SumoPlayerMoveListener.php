<?php

declare(strict_types=1);

namespace bitrule\practice\listener\match;

use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\kit\Kit;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use RuntimeException;

final class SumoPlayerMoveListener implements Listener {

    /** @var int */
    final public const MIN_Y = 7;

    /**
     * @param PlayerMoveEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerMoveEvent(PlayerMoveEvent $ev): void {
        $player = $ev->getPlayer();
        if (!$player->isOnline()) return;
        if ($player->getLocation()->getFloorY() > self::MIN_Y) return;

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel === null || !$duel->getStage() instanceof PlayingStage) return;
        // TODO: Kill player

        if ($duel->getKit()->getName() !== Kit::SUMO) return;

        $duelMember = $duel->getMember($player->getXuid());
        if ($duelMember === null) {
            throw new RuntimeException('Error code 1');
        }

        $duelMember->convertAsSpectator($duel, false);
    }
}