<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\Habu;
use bitrule\practice\registry\QueueRegistry;
use bitrule\practice\TranslationKey;
use bitrule\scoreboard\ScoreboardRegistry;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class LeaveQueueCommand extends Command {

    public function __construct(string $name, string $description, string $usageMessage){
        parent::__construct($name, $description, $usageMessage);

        $this->setPermission('practice.command.leavequeue');
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

        if (($queue = QueueRegistry::getInstance()->getQueueByPlayer($sender)) === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not in a queue.');

            return;
        }

        QueueRegistry::getInstance()->removeQueue($sender);

        $sender->sendMessage(TranslationKey::QUEUE_PLAYER_LEAVED()->build(
            $queue->getKitName(),
            $queue->isRanked() ? 'Ranked' : 'Unranked'
        ));

        ScoreboardRegistry::getInstance()->apply($sender, Habu::LOBBY_SCOREBOARD);
    }
}