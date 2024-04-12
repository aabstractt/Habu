<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\kit\Kit;
use bitrule\practice\registry\KitRegistry;
use JsonException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class KitCreateArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' createkit <name>');

            return;
        }

        $kit = KitRegistry::getInstance()->getKit($args[0]);
        if ($kit === null) {
            $kit = new Kit($args[0], [], [], 'default');
        }

        $kit->setInventoryItems($sender->getInventory()->getContents());
        $kit->setArmorItems($sender->getArmorInventory()->getContents());

        try {
            KitRegistry::getInstance()->createKit($kit);

            $sender->sendMessage(TextFormat::GREEN . 'Kit ' . $args[0] . ' successfully saved!');
        } catch (JsonException $e) {
            $sender->sendMessage(TextFormat::RED . 'Failed to save kit: ' . $e->getMessage());
        }
    }
}