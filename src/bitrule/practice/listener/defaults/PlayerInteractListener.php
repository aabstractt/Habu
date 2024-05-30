<?php

declare(strict_types=1);

namespace bitrule\practice\listener\defaults;

use bitrule\parties\PartiesPlugin;
use bitrule\practice\form\duel\NormalDuelSelector;
use bitrule\practice\form\duel\PartyDuelForm;
use bitrule\practice\registry\DuelRegistry;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\TextFormat;
use function is_string;

final class PlayerInteractListener implements Listener {

    public function onPlayerInteractEvent(PlayerInteractEvent $ev): void {
        $player = $ev->getPlayer();
        if (!$player->isOnline()) return;

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel !== null) return;

        $ev->cancel();

        $tag = $ev->getItem()->getNamedTag()->getTag('ItemType');
        if ($tag === null) return;

        $type = $tag->getValue();
        if (!is_string($type)) return;

        if ($type === 'competitive-duel' || $type === 'unranked-duel') {
            $form = new NormalDuelSelector(TextFormat::BOLD . TextFormat::BLUE . ($type === 'competitive-duel' ? 'Competitive' : 'Friendly' . ' Duels'));
            $form->setup($type === 'competitive-duel');

            $player->sendForm($form);

            return;
        }

        if ($type === 'party-ffa' || $type === 'party-split') {
            $partyAdapter = PartiesPlugin::getInstance()->getPartyAdapter();
            if ($partyAdapter === null) {
                $player->sendMessage(TextFormat::RED . 'Parties plugin is not enabled.');

                return;
            }

            $party = $partyAdapter->getPartyByPlayer($player->getXuid());
            if ($party === null) {
                $player->sendMessage(TextFormat::RED . 'You are not in a party.');

                return;
            }

            if ($party->getOwnership()->getXuid() !== $player->getXuid()) {
                $player->sendMessage(TextFormat::RED . 'You are not the party owner.');

                return;
            }

            $form = new PartyDuelForm('§u' . TextFormat::BOLD . ($type === 'party-ffa' ? 'Party FFA' : 'Party Split'));
            $form->setup($party, $type === 'party-split');

            $player->sendForm($form);
        }
    }
}