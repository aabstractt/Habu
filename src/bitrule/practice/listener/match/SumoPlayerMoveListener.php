<?php

declare(strict_types=1);

namespace bitrule\practice\listener\match;

use bitrule\practice\duel\events\SumoEvent;
use bitrule\practice\duel\stage\impl\KillablePlayingStage;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class SumoPlayerMoveListener implements Listener {

    /**
     * @param PlayerMoveEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerMoveEvent(PlayerMoveEvent $ev): void {
        $player = $ev->getPlayer();
        if (!$player->isOnline()) return;

        SumoEvent::getInstance()->listenPlayerMove($player, $ev->getTo());

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel === null || !$duel->getStage() instanceof PlayingStage) return;

        $cuboid = $duel->getCuboid();
        if ($cuboid === null) {
            throw new RuntimeException('Error code 1');
        }
        if ($cuboid->isVectorInside($player->getLocation())) return;

        if ($player->getGamemode() === GameMode::SPECTATOR) {
            $duel->teleportSpawn($player);

            $player->sendMessage(TextFormat::RED . 'You cannot leave the arena!');

            return;
        }

        $duelMember = $duel->getMember($player->getXuid());
        if ($duelMember === null) {
            throw new RuntimeException('Error code 2');
        }

        $stage = $duel->getStage();
        if (!$stage instanceof KillablePlayingStage) {
            $duelMember->convertAsSpectator($duel, false);

            return;
        }

        $lastAttackerXuid = $duelMember->getLastAttackerXuid();
        $stage->killPlayer(
            $duel,
            $player,
            $lastAttackerXuid !== null ? DuelRegistry::getInstance()->getPlayerObject($lastAttackerXuid) : null,
            EntityDamageEvent::CAUSE_VOID
        );
    }
}