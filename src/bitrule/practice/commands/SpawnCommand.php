<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\registry\ProfileRegistry;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class SpawnCommand extends Command {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('practice.command.spawn');
    }

    /**
     * @param string[] $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'You must be a player to use this command');

            return;
        }

        $profile = ProfileRegistry::getInstance()->getProfile($sender->getXuid());
        if ($profile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        $profile->applyDefaultAttributes($sender, true);

        $sender->teleport(Server::getInstance()->getWorldManager()->getDefaultWorld()->getSpawnLocation());
    }
}