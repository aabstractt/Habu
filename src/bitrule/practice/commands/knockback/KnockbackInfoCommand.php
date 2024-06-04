<?php

declare(strict_types=1);

namespace bitrule\practice\commands\knockback;

use abstractplugin\command\Argument;
use bitrule\practice\kit\Kit;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\KnockbackRegistry;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use function array_filter;
use function array_map;
use function count;
use function implode;

final class KnockbackInfoCommand extends Argument {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function onConsoleExecute(CommandSender $sender, string $commandLabel, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /knockback info <knockback>');

            return;
        }

        $knockbackProfile = KnockbackRegistry::getInstance()->getKnockback($args[0]);
        if ($knockbackProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Knockback profile not found.');

            return;
        }

        $kits = array_map(
            fn(Kit $kit) => $kit->getName(),
            array_filter(
            KitRegistry::getInstance()->getKits(),
            fn($kit) => $kit->getKnockbackProfile() === $knockbackProfile->getName()
            )
        );

        $sender->sendMessage(TextFormat::BLUE . 'Knockback Info for ' . $knockbackProfile->getName());

        if (count($kits) > 0) {
            $sender->sendMessage(TextFormat::GREEN . '  - Kits: ' . TextFormat::YELLOW . implode(', ', $kits));
        }

        $sender->sendMessage(TextFormat::GREEN . '  - Vertical: ' . TextFormat::YELLOW . $knockbackProfile->getVertical());
        $sender->sendMessage(TextFormat::GREEN . '  - Horizontal: ' . TextFormat::YELLOW . $knockbackProfile->getHorizontal());
        $sender->sendMessage(TextFormat::GREEN . '  - Highest Limit: ' . TextFormat::YELLOW . $knockbackProfile->getHighestLimit());
        $sender->sendMessage(TextFormat::GREEN . '  - Hit Delay: ' . TextFormat::YELLOW . $knockbackProfile->getHitDelay());
    }
}