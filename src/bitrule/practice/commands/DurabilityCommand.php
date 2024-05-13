<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Durable;
use pocketmine\lang\Translatable;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class DurabilityCommand extends Command {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission('practice.command.durability');
    }

    /**
     * @param string[] $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage('You must be a player to use this command');

            return;
        }

        $item = $sender->getInventory()->getItemInHand();
        if (!$item instanceof Durable || $item->isUnbreakable()) {
            $sender->sendMessage('This item is unbreakable');

            return;
        }

        $diff = $item->getMaxDurability() - $item->getDamage();
        $percentage = $diff / $item->getMaxDurability() * 100;
        $sender->sendMessage(TextFormat::GREEN . 'Durability: ' . $percentage . '%');
    }
}