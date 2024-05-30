<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\Habu;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\registry\QueueRegistry;
use bitrule\practice\TranslationKey;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

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

        $profile = ProfileRegistry::getInstance()->getProfile($sender->getXuid());
        if ($profile === null) {
            $sender->sendMessage(TextFormat::RED . 'Your profile has not loaded yet.');

            return;
        }

        if (($queue = $profile->getQueue()) === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not in a queue.');

            return;
        }

        QueueRegistry::getInstance()->removeQueue($profile);

        $sender->sendMessage(TranslationKey::PLAYER_QUEUE_LEFT()->build(
            $queue->getKitName(),
            $queue->isRanked() ? 'Ranked' : 'Unranked'
        ));

        Habu::applyScoreboard($sender, ProfileRegistry::LOBBY_SCOREBOARD);
    }
}