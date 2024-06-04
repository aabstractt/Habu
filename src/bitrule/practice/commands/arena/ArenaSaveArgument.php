<?php

declare(strict_types=1);

namespace bitrule\practice\commands\arena;

use abstractplugin\command\Argument;
use abstractplugin\command\PlayerArgumentTrait;
use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\asyncio\FileCopyAsyncTask;
use bitrule\practice\Habu;
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
        $profile = ProfileRegistry::getInstance()->getProfile($sender->getXuid());
        if ($profile === null) {
            $sender->sendMessage(TextFormat::RED . 'Error code 1');

            return;
        }

        $arenaSetup = $profile->getArenaSetup();
        if ($arenaSetup === null) {
            $sender->sendMessage(TextFormat::RED . 'You are not editing an arena');

            return;
        }

        $profile->setArenaSetup(null);

        Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(
            Server::getInstance()->getDataPath() . 'worlds/' . $arenaSetup->getName(),
            Habu::getInstance()->getDataFolder() . 'backups/' . $arenaSetup->getName(),
            function () use ($arenaSetup, $sender): void {
                try {
                    $properties = $arenaSetup->getProperties();
                    if (!isset($properties['type'])) {
                        throw new Exception('Arena type is not set');
                    }

                    $arenaProperties = ArenaProperties::parse($arenaSetup->getName(), $properties['type']);
                    $arenaProperties->setProperties($properties);

                    ArenaRegistry::getInstance()->createArena($arenaProperties);
                    ArenaRegistry::getInstance()->saveAll();

                    $sender->sendMessage(Habu::prefix() . TextFormat::GREEN . 'Arena saved successfully!');

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