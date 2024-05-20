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

        $partyAdapter->processInvitePlayer($sender, $target);
    }
}