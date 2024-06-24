<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\Habu;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\QueueRegistry;
use bitrule\practice\TranslationKey;
use bitrule\scoreboard\ScoreboardRegistry;
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

        if (QueueRegistry::getInstance()->getQueueByPlayer($sender) !== null) {
            $sender->sendMessage(TextFormat::RED . 'You are already in a queue.');

            return;
        }

        $kit = KitRegistry::getInstance()->getKit($args[0]);
        if ($kit === null) {
            $sender->sendMessage(TextFormat::RED . 'Kit not found.');

            return;
        }

        $queue = QueueRegistry::getInstance()->createQueue($sender->getXuid(), $kit->getName(), isset($args[1]) && $args[1] === 'ranked');
        if ($queue === null) return;

        $sender->sendMessage(TranslationKey::QUEUE_PLAYER_JOINED()->build(
            $kit->getName(),
            $queue->isRanked() ? 'Ranked' : 'Unranked'
        ));

        ScoreboardRegistry::getInstance()->apply($sender, Habu::QUEUE_SCOREBOARD);
    }
}