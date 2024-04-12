<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\duel\queue\Queue;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\registry\QueueRegistry;
use bitrule\practice\Practice;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

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

        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($sender->getXuid());
        if ($localProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Your profile has not loaded yet.');

            return;
        }

        if ($localProfile->getQueue() !== null) {
            $sender->sendMessage(TextFormat::RED . 'You are already in a queue.');

            return;
        }

        $kit = KitRegistry::getInstance()->getKit($args[0]);
        if ($kit === null) {
            $sender->sendMessage(TextFormat::RED . 'Kit not found.');

            return;
        }

        $sender->sendMessage(TextFormat::GREEN . 'You have joined the queue for ' . TextFormat::AQUA . $kit->getName() . TextFormat::GREEN . '.');

        QueueRegistry::getInstance()->createQueue(
            $localProfile,
            $kit->getName(),
            false,
            function (Queue $matchQueue) use ($sender, $localProfile): void {
                if (!$sender->isOnline()) return;

                $localProfile->setQueue($matchQueue);
                Practice::setProfileScoreboard($sender, ProfileRegistry::QUEUE_SCOREBOARD);
            }
        );
    }
}