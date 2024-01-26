<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\kit\Kit;
use bitrule\practice\Practice;
use Exception;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_rand;
use function in_array;
use function is_array;
use function is_string;

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

    /**
     * @throws \JsonException
     */
    public function saveAll(): void {
        $config = new Config(Practice::getInstance()->getDataFolder() . 'arenas.yml');

        foreach ($this->arenas as $arena) {
            $config->set($arena->getName(), $arena->serialize());
        }

        $config->save();
    }

    /**
     * @param AbstractArena $arena
     */
    public function createArena(AbstractArena $arena): void {
        $this->arenas[$arena->getName()] = $arena;
    }

    /**
     * @param string $name
     */
    public function removeArena(string $name): void {
        unset($this->arenas[$name]);
    }

    /**
     * @param string $name
     *
     * @return AbstractArena|null
     */
    public function getArena(string $name): ?AbstractArena {
        return $this->arenas[$name] ?? null;
    }

    /**
     * @param Kit $kit
     *
     * @return AbstractArena|null
     */
    public function getRandomArena(Kit $kit): ?AbstractArena {
        $arenas = [];

        foreach ($this->arenas as $arena) {
            if (!in_array($kit->getName(), $arena->getKits(), true)) continue;

            $arenas[] = $arena;
        }

        return $arenas[array_rand($arenas)] ?? null;
    }
}