<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\form\arena\ArenaSetupForm;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use function count;

final class ArenaCreateArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' create <world>');

            return;
        }

        $profile = ProfileRegistry::getInstance()->getProfile($sender->getXuid());
        if ($profile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        if ($profile->getArenaSetup() !== null) {
            $sender->sendMessage(TextFormat::RED . 'You are already editing an arena');

            return;
        }

        $worldManager = Server::getInstance()->getWorldManager();
        if (!$worldManager->loadWorld($args[0])) {
            $sender->sendMessage(TextFormat::RED . 'World not generated');

            return;
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($args[0]);
        if ($world === null) {
            $sender->sendMessage(TextFormat::RED . 'World not found');

            return;
        }

        $form = new ArenaSetupForm();
        $form->setup($world);

        $sender->sendForm($form);
    }
}