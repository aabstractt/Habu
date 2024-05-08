<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\KnockbackRegistry;
use Exception;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class ArenaKnockbackArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' knockback <arena> <profile>');

            return;
        }

        $arena = ArenaRegistry::getInstance()->getArena($args[0]);
        if ($arena === null) {
            $sender->sendMessage(TextFormat::RED . 'Arena with that name does not exist');

            return;
        }

        $knockbackProfile = KnockbackRegistry::getInstance()->getKnockback($args[1]);
        if ($knockbackProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Knockback profile with that name does not exist');

            return;
        }

        try {
            $arena->setKnockbackProfile($knockbackProfile->getName());
            ArenaRegistry::getInstance()->saveAll();

            $sender->sendMessage(TextFormat::GREEN . 'Knockback profile set to ' . $knockbackProfile->getName() . ' for arena ' . $arena->getName());
        } catch (Exception $e) {
            $sender->sendMessage(TextFormat::RED . 'An error occurred while saving the arena: ' . $e->getMessage());
        }
    }
}