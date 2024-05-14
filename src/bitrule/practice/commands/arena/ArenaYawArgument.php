<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\entity\Location;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function is_numeric;

final class ArenaYawArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($sender->getXuid());
        if ($localProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        $arenaSetup = $localProfile->getArenaSetup();
        if ($arenaSetup === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not editing an arena');

            return;
        }

        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' yaw <spawn>');

            return;
        }

        if (!is_numeric($args[0])) {
            $sender->sendMessage(TextFormat::RED . 'Yaw must be a number');

            return;
        }

        $spawnId = (int) $args[0];
        if ($spawnId < 1 || $spawnId > 2) {
            $sender->sendMessage(TextFormat::RED . 'Spawn must be 1 or 2');

            return;
        }

        $location = $spawnId === 1 ? $arenaSetup->getFirstPosition() : $arenaSetup->getSecondPosition();
        if ($location === null) {
            $sender->sendMessage(TextFormat::RED . 'Position not set');

            return;
        }

        $location = Location::fromObject($location, $sender->getWorld(), $sender->getLocation()->yaw, $sender->getLocation()->pitch);
        if ($spawnId === 1) {
            $arenaSetup->setFirstPosition($location);
        } else {
            $arenaSetup->setSecondPosition($location);
        }

        $sender->sendMessage(TextFormat::GREEN . 'Yaw set for spawn ' . $spawnId);
    }
}