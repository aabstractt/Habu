<?php

declare(strict_types=1);

namespace bitrule\practice\listener\match;

use bitrule\practice\arena\impl\FireballFightArenaProperties;
use bitrule\practice\event\player\PlayerKitAppliedEvent;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\Wool;
use pocketmine\event\Listener;
use pocketmine\item\Armor;
use pocketmine\item\ItemBlock;
use pocketmine\player\GameMode;
use RuntimeException;

final class PlayerKitAppliedListener implements Listener {

    /**
     * @param PlayerKitAppliedEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerKitAppliedEvent(PlayerKitAppliedEvent $ev): void {
        $player = $ev->getPlayer();
        if (!$player->isOnline()) return;

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel === null) return;
        if (!$duel->getArenaProperties() instanceof FireballFightArenaProperties) return;

        $spawnId = $duel->getSpawnId($player->getXuid());
        if ($spawnId === -1) {
            throw new RuntimeException('Spawn ID not found');
        }

        $player->setGamemode(GameMode::SURVIVAL);

        $color = $spawnId === FireballFightArenaProperties::TEAM_RED_ID ? DyeColor::RED() : DyeColor::BLUE();

        foreach ($player->getArmorInventory()->getContents() as $slot => $item) {
            if (!$item instanceof Armor) continue;

            $item->setCustomColor($color->getRgbValue());
            $player->getArmorInventory()->setItem($slot, $item);
        }

        $allWool = $player->getInventory()->all(VanillaBlocks::WOOL()->asItem());
        foreach ($allWool as $slot => $item) {
            if (!$item instanceof ItemBlock) continue;

            $block = $item->getBlock();
            if (!$block instanceof Wool) continue;

            $block->setColor($color);
            $player->getInventory()->setItem($slot, $block->asItem()->setCount($item->getCount()));
        }
    }
}