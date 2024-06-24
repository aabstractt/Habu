<?php

declare(strict_types=1);

namespace bitrule\practice\commands\events;

use abstractplugin\command\Argument;
use bitrule\practice\duel\events\SumoEvent;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class EventsJoinArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'This command can only be used in-game');

            return;
        }

        if ($sender->getWorld() !== Server::getInstance()->getWorldManager()->getDefaultWorld()) {
            $sender->sendMessage(TextFormat::RED . 'You can only use this command in the default world');

            return;
        }

        if (!SumoEvent::getInstance()->isEnabled()) {
            $sender->sendMessage(TextFormat::RED . 'The event is not started');

            return;
        }

        SumoEvent::getInstance()->joinPlayer($sender, true);
    }
}