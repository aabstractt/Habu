<?php

declare(strict_types=1);

namespace bitrule\practice\commands\knockback;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\commands\KnockbackProfileCommand;
use bitrule\practice\registry\KnockbackRegistry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function is_float;

final class KnockbackHighestLimitCommand extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param string[]  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) < 2) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' highestlimit <name> <value>');

            return;
        }

        $knockbackProfile = KnockbackRegistry::getInstance()->getKnockback($args[0]);
        if ($knockbackProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Knockback profile with that name does not exist');

            return;
        }

        if (!is_float($args[1])) {
            $sender->sendMessage(TextFormat::RED . 'Value must be a float');

            return;
        }

        $sender->sendMessage(KnockbackProfileCommand::PREFIX . TextFormat::GREEN . 'Set highest-limit knockback for ' . $args[0] . ' to ' . $args[1] . '.');

        $knockbackProfile->setHighestLimit($args[1]);
        KnockbackRegistry::getInstance()->saveAll();
    }
}