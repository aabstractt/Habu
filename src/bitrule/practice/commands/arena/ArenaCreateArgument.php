<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\arena\AbstractArena;
use bitrule\practice\manager\ArenaManager;
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
        if (count($args) < 3) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' create <type>');

            return;
        }

        if (ArenaManager::getInstance()->getArena($args[0]) !== null) {
            $sender->sendMessage(TextFormat::RED . 'An arena with that name already exists.');

            return;
        }
    }
}