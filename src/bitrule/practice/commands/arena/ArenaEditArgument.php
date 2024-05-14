<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\form\arena\ArenaSetupForm;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class ArenaEditArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' edit <world>');

            return;
        }

        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($sender->getXuid());
        if ($localProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        if ($localProfile->getArenaSetup() !== null) {
            $sender->sendMessage(TextFormat::RED . 'You are already editing an arenaProperties');

            return;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($args[0]);
        if ($world === null) {
            $sender->sendMessage(TextFormat::RED . 'World not found');

            return;
        }

        $arenaProperties = ArenaRegistry::getInstance()->getArena($world->getFolderName());
        if ($arenaProperties === null) {
            $sender->sendMessage(TextFormat::RED . 'Arena not found');

            return;
        }

        try {
            $arenaSetup = AbstractArenaSetup::from(ArenaProperties::getArenaTypeByKit($arenaProperties->getPrimaryKit()));
            $arenaSetup->load($arenaProperties);
            $arenaSetup->setup($sender);

            $localProfile->setArenaSetup($arenaSetup);

            $sender->sendMessage(TextFormat::GREEN . 'Arena setup for ' . $world->getFolderName() . ' started.');
        } catch (\Exception $e) {
            $sender->sendMessage(TextFormat::RED . 'An error occurred while starting the arena setup: ' . $e->getMessage());

            Server::getInstance()->getLogger()->error('An error occurred while starting the arena setup: ' . $e->getMessage());
            Server::getInstance()->getLogger()->logException($e);
        }
    }
}