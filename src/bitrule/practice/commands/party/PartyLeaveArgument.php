<?php

declare(strict_types=1);

namespace bitrule\practice\commands\party;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\Habu;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class PartyLeaveArgument extends Argument {
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

        $partyAdapter->processLeavePlayer($sender);
    }
}