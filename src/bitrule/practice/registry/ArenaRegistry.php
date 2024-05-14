<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\asyncio\FileCopyAsyncTask;
use bitrule\practice\kit\Kit;
use bitrule\practice\Practice;
use Closure;
use Exception;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_rand;
use function count;
use function is_array;
use function is_string;

final class ArenaRegistry {
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

    /** @var array<string, ArenaProperties> */
    private array $arenas = [];

    /**
     * Load all arenas from the arenas.yml file.
     */
    public function loadAll(): void {
        $config = new Config(Practice::getInstance()->getDataFolder() . 'arenas.yml', Config::YAML);
        foreach ($config->getAll() as $arenaName => $properties) {
            if (!is_string($arenaName) || !is_array($properties)) {
                throw new RuntimeException('Invalid arena data');
            }

            try {
                $this->createArena($arenaProperties = ArenaProperties::parse($arenaName, $properties));

                $arenaProperties->setup($properties);
            } catch (Exception $e) {
                Practice::getInstance()->getLogger()->error('Failed to load arena ' . $arenaName . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Save all arenas to the arenas.yml file.
     */
    public function saveAll(): void {
        $config = new Config(Practice::getInstance()->getDataFolder() . 'arenas.yml');

        foreach ($this->arenas as $arena) {
            $config->set($arena->getOriginalName(), $arena->getOriginalProperties());
        }

        try {
            $config->save();
        } catch (Exception $e) {
            Practice::getInstance()->getLogger()->error('Failed to save arenas: ' . $e->getMessage());
        }
    }

    /**
     * Add the arena to the arenas list.
     *
     * @param ArenaProperties $arenaProperties
     */
    public function createArena(ArenaProperties $arenaProperties): void {
        $this->arenas[$arenaProperties->getOriginalName()] = $arenaProperties;
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
     * @return ArenaProperties|null
     */
    public function getArena(string $name): ?ArenaProperties {
        return $this->arenas[$name] ?? null;
    }

    /**
     * Find a random arena that has the specified kit.
     *
     * @param Kit $kit
     *
     * @return ArenaProperties|null
     */
    public function getRandomArena(Kit $kit): ?ArenaProperties {
        $arenasFiltered = [];

        foreach ($this->arenas as $arena) {
            if ($arena->getArenaType() !== $kit->getName()) continue;

            $arenasFiltered[] = $arena;
        }

        if (count($arenasFiltered) === 0) return null;

        return $arenasFiltered[array_rand($arenasFiltered)] ?? null;
    }

    /**
     * @param string               $arenaName
     * @param string               $worldName
     * @param Closure(): void $onComplete
     */
    public function loadWorld(string $arenaName, string $worldName, Closure $onComplete): void {
       Server::getInstance()->getAsyncPool()->submitTask(new FileCopyAsyncTask(
           Practice::getInstance()->getDataFolder() . 'backups/' . $arenaName,
           Server::getInstance()->getDataPath() . 'worlds/' . $worldName,
           $onComplete
       ));
    }
}