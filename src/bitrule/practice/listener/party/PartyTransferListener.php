<?php

declare(strict_types=1);

namespace bitrule\practice\listener\party;

use bitrule\parties\event\PartyDisbandEvent;
use bitrule\parties\event\PartyTransferEvent;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\Listener;
use pocketmine\Server;

final class PartyTransferListener implements Listener {

    /**
     * @param PartyTransferEvent $ev
     *
     * @priority NORMAL
     */
    public function onPartyTransferEvent(PartyTransferEvent $ev): void {
        $ownership = $ev->getOwnership();
        if (!$ownership->isOnline()) return;

        $duelRegistry = DuelRegistry::getInstance();
        if ($duelRegistry->getDuelByPlayer($ownership->getXuid()) === null) {
            Profile::setDefaultAttributes($ownership);
        }

        $player = Server::getInstance()->getPlayerExact($ev->getParty()->getOwnership()->getName());
        if ($player === null || !$player->isOnline()) return;
        if ($duelRegistry->getDuelByPlayer($player->getXuid()) !== null) return;

        Profile::setDefaultAttributes($player);
    }
}