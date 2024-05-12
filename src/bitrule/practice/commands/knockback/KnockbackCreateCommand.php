<?php

declare(strict_types=1);

namespace bitrule\practice\commands\knockback;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\commands\KnockbackProfileCommand;
use bitrule\practice\kit\KnockbackProfile;
use bitrule\practice\registry\KnockbackRegistry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;

final class KnockbackCreateCommand extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param string[]  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        if (count($args) === 0) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /' . $label . ' create <name>');

            return;
        }

        if (KnockbackRegistry::getInstance()->getKnockback($args[0]) !== null) {
            $sender->sendMessage(TextFormat::RED . 'Knockback profile with that name already exists');

            return;
        }

        KnockbackRegistry::getInstance()->registerNew(KnockbackProfile::empty($args[0]));
        KnockbackRegistry::getInstance()->saveAll();

        $sender->sendMessage(KnockbackProfileCommand::PREFIX . TextFormat::GREEN . 'Knockback profile ' . $args[0] . ' successfully created!');
   }
}