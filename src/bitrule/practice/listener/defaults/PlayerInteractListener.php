<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use bitrule\practice\manager\ProfileManager;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\ItemTypeIds;
use pocketmine\utils\TextFormat;

final class PlayerInteractListener implements Listener {

    /**
     * @param PlayerInteractEvent $ev
     *
     * @priority NORMAL
     */
    public function onPlayerInteractEvent(PlayerInteractEvent $ev): void {
        $player = $ev->getPlayer();

        // Prevent handle event if the player not is online
        if (!$player->isOnline()) return;

        $item = $ev->getItem();
        if ($item->getTypeId() !== ItemTypeIds::STICK) return;

        if ($item->getNamedTag()->getTag('arena') === null) return;

        $localProfile = ProfileManager::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) return;

        $arenaSetup = $localProfile->getArenaSetup();
        if ($arenaSetup === null) return;

        $player->sendMessage(TextFormat::GREEN . 'Position for step ' . $arenaSetup->getSpawnStep() . ' set!');

        $arenaSetup->setPositionByStep($arenaSetup->getSpawnStep(), $ev->getBlock()->getPosition());
        $arenaSetup->increaseSpawnStep();
    }
}