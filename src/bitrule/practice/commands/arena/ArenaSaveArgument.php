<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\arena\AbstractArena;
use bitrule\practice\manager\ArenaManager;
use bitrule\practice\manager\ProfileManager;
use Exception;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class ArenaSaveArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $localProfile = ProfileManager::getInstance()->getLocalProfile($sender->getXuid());
        if ($localProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        $arenaSetup = $localProfile->getArenaSetup();
        if ($arenaSetup === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not editing an arena');

            return;
        }

        try {
            $arenaSetup->submit($arena = AbstractArena::createEmpty($arenaSetup->getName(), $arenaSetup->getType()));

            ArenaManager::getInstance()->createArena($arena);
            ArenaManager::getInstance()->saveAll();

            $sender->sendMessage(TextFormat::GREEN . 'Arena saved successfully!');
        } catch (Exception $e) {
            $sender->sendMessage(TextFormat::RED . 'An error occurred while saving the arena: ' . $e->getMessage());
        }
    }
}