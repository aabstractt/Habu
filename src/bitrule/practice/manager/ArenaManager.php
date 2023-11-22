<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\Practice;
use bitrule\practice\arena\AbstractArena;
use Exception;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class ArenaManager {
    use SingletonTrait;

    /** @var array<string, AbstractArena> */
    private array $arenas = [];

    public function init(): void {
        $config = new Config(Practice::getInstance()->getDataFolder() . 'arenas.yml', Config::YAML);
        foreach ($config->getAll() as $arenaName => $arenaData) {
            if (!is_string($arenaName) || !is_array($arenaData)) {
                throw new RuntimeException('Invalid arena data');
            }

            try {
                $this->createArena(AbstractArena::createFromArray($arenaName, $arenaData));
            } catch (Exception $e) {
                Practice::getInstance()->getLogger()->error('Failed to load arena ' . $arenaName . ': ' . $e->getMessage());
            }
        }
    }

    public function createArena(AbstractArena $arena): void {
        $this->arenas[$arena->getName()] = $arena;
    }

    public function removeArena(string $name): void {
        unset($this->arenas[$name]);
    }

    public function getArena(string $name): ?AbstractArena {
        return $this->arenas[$name] ?? null;
    }
}