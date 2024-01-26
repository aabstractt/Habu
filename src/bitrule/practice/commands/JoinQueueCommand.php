<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\manager\KitManager;
use bitrule\practice\manager\MatchManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class JoinQueueCommand extends Command {

    public function __construct(string $name, string $description, string $usageMessage){
        parent::__construct($name, $description, $usageMessage);

        $this->setPermission('practice.command.joinqueue');
    }

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void{
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'This command can only be used in-game.');

            return;
        }

        if (count($args) < 1) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /joinqueue <kit>');

            return;
        }

        $kit = KitManager::getInstance()->getKit($args[0]);
        if ($kit === null) {
            $sender->sendMessage(TextFormat::RED . 'Kit not found.');

            return;
        }

        $sender->sendMessage(TextFormat::GREEN . 'You have joined the queue for ' . TextFormat::AQUA . $kit->getName() . TextFormat::GREEN . '.');

        MatchManager::getInstance()->createQueue(
            $sender->getXuid(),
            $kit->getName(),
            false
        );
    }
}