<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\Habu;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function is_numeric;

final class ArenaCornerArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $profile = ProfileRegistry::getInstance()->getProfile($sender->getXuid());
        if ($profile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        $arenaSetup = $profile->getArenaSetup();
        if ($arenaSetup === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not editing an arena');

            return;
        }

        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' corner <id>');

            return;
        }

        if (!is_numeric($args[0])) {
            $sender->sendMessage(TextFormat::RED . 'Yaw must be a number');

            return;
        }

        $spawnId = (int) $args[0];
        if ($spawnId < 1 || $spawnId > 2) {
            $sender->sendMessage(TextFormat::RED . 'Id must be 1 or 2');

            return;
        }

        if ($spawnId === 1) {
            $arenaSetup->setFirstCorner($sender->getLocation());
        } else {
            $arenaSetup->setSecondCorner($sender->getLocation());
        }

        $sender->sendMessage(Habu::prefix() . TextFormat::GREEN . 'Corner ' . $spawnId . ' set');
    }
}