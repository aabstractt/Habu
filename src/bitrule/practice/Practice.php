<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\commands\ArenaMainCommand;
use bitrule\practice\listener\defaults\PlayerInteractListener;
use bitrule\practice\listener\defaults\PlayerJoinListener;
use bitrule\practice\listener\defaults\PlayerQuitListener;
use bitrule\practice\manager\ArenaManager;
use bitrule\practice\manager\KitManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class Practice extends PluginBase {
    use SingletonTrait;

    protected function onEnable(): void {
        self::setInstance($this);

        $bootstrap = 'phar://' . $this->getServer()->getPluginPath() . $this->getName() . '.phar/vendor/autoload.php';
        if (!is_file($bootstrap)) {
            $this->getLogger()->error('Could not find autoload.php in plugin phar, directory: ' . $bootstrap);
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        require_once $bootstrap;

        $this->saveDefaultConfig();

        KitManager::getInstance()->loadAll();
        ArenaManager::getInstance()->init();

        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        $this->getServer()->getCommandMap()->registerAll('bitrule', [
            new ArenaMainCommand()
        ]);
    }
}