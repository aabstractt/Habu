<?php

declare(strict_types=1);

namespace bitrule\practice\listener\world;

use bitrule\practice\duel\stage\impl\BlockBreakStageListener;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\entity\Location;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\ItemTypeIds;
use pocketmine\utils\TextFormat;

final class BlockBreakListener implements Listener {

    /**
     * @param BlockBreakEvent $ev
     *
     * @priority NORMAL
     */
    public function onBlockBreakEvent(BlockBreakEvent $ev): void {
        $player = $ev->getPlayer();

        // Prevent handle event if the player not is online
        if (!$player->isOnline()) return;

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        $stage = $duel?->getStage();
        if ($duel !== null && $stage instanceof BlockBreakStageListener) {
            $stage->onBlockBreakEvent($duel, $player, $ev);
        }

        $profile = ProfileRegistry::getInstance()->getProfile($player->getXuid());
        if ($profile === null) return;

        $arenaSetup = $profile->getArenaSetup();
        if ($arenaSetup === null || !$arenaSetup->isStarted()) return;

        $item = $ev->getItem();
        if ($item->getTypeId() !== ItemTypeIds::GOLDEN_APPLE) {
            $ev->cancel();
        }

        if ($item->getTypeId() !== ItemTypeIds::STICK && $item->getTypeId() !== ItemTypeIds::GOLDEN_APPLE) {
            if ($player->isSneaking()) {
                $arenaSetup->decreaseSpawnStep();
            } else {
                $arenaSetup->increaseSpawnStep();
            }

            $player->sendMessage(TextFormat::BLUE . 'You are now setting position for step ' . $arenaSetup->getSpawnStep());

            return;
        }

        if ($item->getNamedTag()->getTag('arena') === null) return;

        $player->sendMessage(TextFormat::GREEN . 'Position for step ' . $arenaSetup->getSpawnStep() . ' set!');

        $position = $ev->getBlock()->getPosition();
        if ($arenaSetup->getSpawnStep() < 2) $position = $position->add(0, 1, 0);

        $arenaSetup->setPositionByStep($arenaSetup->getSpawnStep(), Location::fromObject($position, $player->getWorld()));
        $arenaSetup->increaseSpawnStep();

        $player->sendMessage(TextFormat::YELLOW . 'You are now setting position for step ' . $arenaSetup->getSpawnStep());
    }
}