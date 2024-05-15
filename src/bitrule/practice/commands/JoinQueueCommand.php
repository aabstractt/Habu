<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\duel\queue\Queue;
use bitrule\practice\Practice;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\registry\QueueRegistry;
use bitrule\practice\TranslationKey;
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

        $profile = ProfileRegistry::getInstance()->getprofile($sender->getXuid());
        if ($profile === null) {
            $sender->sendMessage(TextFormat::RED . 'Your profile has not loaded yet.');

            return;
        }

        if ($profile->getQueue() !== null) {
            $sender->sendMessage(TextFormat::RED . 'You are already in a queue.');

            return;
        }

        $kit = KitRegistry::getInstance()->getKit($args[0]);
        if ($kit === null) {
            $sender->sendMessage(TextFormat::RED . 'Kit not found.');

            return;
        }

        QueueRegistry::getInstance()
            ->createQueue($profile, $kit->getName(), isset($args[1]) && $args[1] === 'ranked')
            ->onCompletion(
                function (Queue $queue) use ($profile, $sender): void {
                    $sender->sendMessage(TranslationKey::PLAYER_QUEUE_JOINED()->build(
                        $queue->getKitName(),
                        $queue->isRanked() ? 'Ranked' : 'Unranked'
                    ));

                    $profile->setQueue($queue);

                    Practice::setProfileScoreboard($sender, ProfileRegistry::QUEUE_SCOREBOARD);
                },
                fn() => $sender->sendMessage(TextFormat::RED . 'An error occurred while joining the queue.')
            );
    }
}