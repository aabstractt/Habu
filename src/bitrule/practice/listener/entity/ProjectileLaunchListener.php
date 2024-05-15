<?php

declare(strict_types=1);

namespace bitrule\practice\listener\entity;

use bitrule\practice\registry\DuelRegistry;
use pocketmine\entity\projectile\EnderPearl;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\Listener;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function microtime;

final class ProjectileLaunchListener implements Listener {

    /**
     * @param ProjectileLaunchEvent $ev
     *
     * @priority NORMAL
     */
    public function onProjectileLaunchEvent(ProjectileLaunchEvent $ev): void {
        $entity = $ev->getEntity();
        if (!$entity instanceof EnderPearl) return;

        $owningEntity = $entity->getOwningEntity();
        if (!$owningEntity instanceof Player) return;

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($owningEntity->getXuid());
        if ($duel !== null) {
            $duelPlayer = $duel->getPlayer($owningEntity->getXuid());
            if ($duelPlayer === null) {
                throw new RuntimeException('Player not found in the duel');
            }

            $remaining = $duelPlayer->getRemainingEnderPearlCountdown();
            if ($remaining > 0.0) {
                $owningEntity->sendMessage(TextFormat::RED . 'You can use an ender pearl in ' . $remaining . ' seconds.');

                $ev->cancel();

                return;
            }

            $duelPlayer->setEnderPearlCountdown(microtime(true) + 15);

            $owningEntity->sendMessage(TextFormat::GREEN . 'You are now on countdown for Ender Pearl!');
        }
    }
}