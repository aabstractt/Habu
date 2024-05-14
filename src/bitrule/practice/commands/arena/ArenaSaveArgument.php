<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\asyncio\FileCopyAsyncTask;
use bitrule\practice\Practice;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\ProfileRegistry;
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

        $localProfile->setArenaSetup(null);

        Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(
            Server::getInstance()->getDataPath() . 'worlds/' . $arenaSetup->getName(),
            Practice::getInstance()->getDataFolder() . 'backups/' . $arenaSetup->getName(),
            function () use ($arenaSetup, $sender): void {
                try {
                    $arenaProperties = ArenaProperties::parse($arenaSetup->getName(), $properties = $arenaSetup->getProperties());
                    $arenaProperties->setup($properties);

                    echo 'Creating' . PHP_EOL;

                    ArenaRegistry::getInstance()->createArena($arenaProperties);
                    ArenaRegistry::getInstance()->saveAll();

                    $sender->sendMessage(TextFormat::GREEN . 'Arena saved successfully!');

                    Server::getInstance()->getLogger()->info('Arena backup saved successfully!');
                } catch (Exception $e) {
                    $sender->sendMessage(TextFormat::RED . 'An error occurred while saving the arena: ' . $e->getMessage());

                    Server::getInstance()->getLogger()->error('An error occurred while saving the arena: ' . $e->getMessage());
                    Server::getInstance()->getLogger()->logException($e);
                }
            }
        ));

    }
}