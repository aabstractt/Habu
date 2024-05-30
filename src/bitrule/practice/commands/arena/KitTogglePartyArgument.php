<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use bitrule\practice\registry\KitRegistry;
use Exception;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function count;

final class KitTogglePartyArgument extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $commandLabel . ' toggleparty <kit>');

            return;
        }

        $kit = KitRegistry::getInstance()->getKit($args[0]);
        if ($kit === null) {
            $sender->sendMessage(TextFormat::RED . 'Kit not found.');

            return;
        }

        try {
            $kit->setPartyPlayable(!$kit->isPartyPlayable());
            KitRegistry::getInstance()->createKit($kit);

            $sender->sendMessage(TextFormat::GREEN . 'Kit ' . $kit->getName() . ' party playable set to ' . ($kit->isPartyPlayable() ? 'true' : 'false') . '.');
        } catch (Exception $e) {
            $sender->sendMessage(TextFormat::RED . 'Failed to save kit: ' . $e->getMessage());
        }
    }
}