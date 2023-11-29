<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\commands\ArenaMainCommand;
use bitrule\practice\listener\defaults\PlayerJoinListener;
use bitrule\practice\listener\defaults\PlayerQuitListener;
use bitrule\practice\manager\ArenaManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class Practice extends PluginBase {
    use SingletonTrait;

    protected function onEnable(): void {
        self::setInstance($this);

        $this->saveDefaultConfig();

        ArenaManager::getInstance()->init();

        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        $this->getServer()->getCommandMap()->registerAll('bitrule', [
            new ArenaMainCommand()
        ]);
    }
}