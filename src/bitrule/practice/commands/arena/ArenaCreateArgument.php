<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\form\arena\ArenaSetupForm;
use bitrule\practice\manager\PlayerManager;
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
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' create <type>');

            return;
        }

        $localPlayer = PlayerManager::getInstance()->getLocalPlayer($sender->getXuid());
        if ($localPlayer === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        if ($localPlayer->getArenaSetup() !== null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 2');

            return;
        }

        $form = new ArenaSetupForm();
        $form->init();

        $sender->sendForm($form);
    }
}