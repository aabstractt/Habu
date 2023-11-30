<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\form\arena\ArenaSetupForm;
use bitrule\practice\manager\ProfileManager;
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

        $localProfile = ProfileManager::getInstance()->getLocalProfile($sender->getXuid());
        if ($localProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        if ($localProfile->getArenaSetup() !== null) {
            $sender->sendMessage(TextFormat::RED . 'You are already editing an arena');

            return;
        }

        $form = new ArenaSetupForm();
        $form->setup();

        $sender->sendForm($form);
    }
}