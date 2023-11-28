<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\arena\AbstractArena;
use bitrule\practice\manager\ArenaManager;
use Exception;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class ArenaCreateArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' create <type> <schematic>');

            return;
        }

        if (ArenaManager::getInstance()->getArena($args[1]) !== null) {
            $sender->sendMessage(TextFormat::RED . 'An arena with that name already exists.');

            return;
        }

        try {
            $arena = AbstractArena::createEmpty($args[1], $args[0]);
            ArenaManager::getInstance()->createArena($arena);

            $sender->sendMessage(TextFormat::GREEN . 'Arena ' . $arena->getSchematic()->getName() . ' created.');
        } catch (Exception $e) {
            $sender->sendMessage(TextFormat::RED . 'Failed to create arena: ' . $e->getMessage());
        }
    }
}