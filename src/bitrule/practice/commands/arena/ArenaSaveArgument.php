<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\asyncio\FileCopyAsyncTask;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\Practice;
use Exception;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class ArenaSaveArgument extends Argument {
    use PlayerArgumentTrait;

    /**
     * @param Player $sender
     * @param string $label
     * @param array  $args
     */
    public function onPlayerExecute(Player $sender, string $label, array $args): void {
        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($sender->getXuid());
        if ($localProfile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        $arenaSetup = $localProfile->getArenaSetup();
        if ($arenaSetup === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not editing an arena');

            return;
        }

        Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(
            Server::getInstance()->getDataPath() . 'worlds/' . $arenaSetup->getName(),
            Practice::getInstance()->getDataFolder() . 'backups/' . $arenaSetup->getName(),
            function () use ($arenaSetup, $sender): void {
                try {
                    $arenaSetup->submit($arena = AbstractArena::createEmpty($arenaSetup->getName(), $arenaSetup->getType()));

                    ArenaRegistry::getInstance()->createArena($arena);
                    ArenaRegistry::getInstance()->saveAll();

                    $sender->sendMessage(TextFormat::GREEN . 'Arena saved successfully!');

                    Server::getInstance()->getLogger()->info('Arena backup saved successfully!');
                } catch (Exception $e) {
                    $sender->sendMessage(TextFormat::RED . 'An error occurred while saving the arena: ' . $e->getMessage());
                }
            }
        ));

    }
}