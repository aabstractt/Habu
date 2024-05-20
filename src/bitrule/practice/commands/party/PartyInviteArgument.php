<?php

declare(strict_types=1);

namespace bitrule\practice\commands\party;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\Habu;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class PartyInviteArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $partyAdapter = Habu::getInstance()->getPartyAdapter();
        if ($partyAdapter === null) {
            $sender->sendMessage(TextFormat::RED . 'Parties are not enabled');

            return;
        }

        if (count($args) < 1) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /party invite <player>');

            return;
        }

        $target = Server::getInstance()->getPlayerByPrefix($args[0]);
        if ($target === null || !$target->isOnline()) {
            $sender->sendMessage(TextFormat::RED . $args[0] . ' not is online');

            return;
        }

        $party = $partyAdapter->getPartyByPlayer($sender);
        if ($party === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not in a party');

            return;
        }

        if ($party->isMember($target->getXuid())) {
            $sender->sendMessage(TextFormat::RED . $target->getName() . ' is already in your party');

            return;
        }

        if ($partyAdapter->getPartyByPlayer($target) !== null) {
            $sender->sendMessage(TextFormat::RED . $target->getName() . ' is already in a party');

            return;
        }

        if ($party->isPendingInvite($target->getXuid())) {
            $sender->sendMessage(TextFormat::RED . 'You have already invited ' . $target->getName());

            return;
        }

        $partyAdapter->processInvitePlayer($sender, $target, $party);
    }
}