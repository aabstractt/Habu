<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\manager\ArenaManager;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\SingletonTrait;

final class Practice extends PluginBase {
    use SingletonTrait;

    protected function onEnable(): void {
        self::setInstance($this);

        ArenaManager::getInstance()->init();
    }
}