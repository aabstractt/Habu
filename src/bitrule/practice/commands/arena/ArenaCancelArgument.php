<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class ArenaCancelArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $profile = ProfileRegistry::getInstance()->getProfile($sender->getXuid());
        if ($profile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        if ($profile->getArenaSetup() === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not editing an arena');

            return;
        }

        $profile->setArenaSetup(null);

        $sender->sendMessage(TextFormat::GREEN . 'Arena setup cancelled');

        Profile::setDefaultAttributes($sender);

        $sender->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
    }
}