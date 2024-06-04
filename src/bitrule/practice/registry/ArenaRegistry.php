<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\asyncio\FileCopyAsyncTask;
use bitrule\practice\Habu;
use bitrule\practice\kit\Kit;
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
        $config = new Config(Habu::getInstance()->getDataFolder() . 'arenas.yml', Config::YAML);
        foreach ($config->getAll() as $arenaName => $properties) {
            if (!is_string($arenaName) || !is_array($properties)) {
                throw new RuntimeException('Invalid arena data');
            }

            try {
                if (!isset($properties['type'])) {
                    throw new Exception('Arena type is not set');
                }

                $arenaProperties = ArenaProperties::parse($arenaName, $properties['type']);
                $arenaProperties->setProperties($properties);
                $arenaProperties->adaptProperties();

                $this->createArena($arenaProperties);
            } catch (Exception $e) {
                Habu::getInstance()->getLogger()->error('Failed to load arena ' . $arenaName . ': ' . $e->getMessage());
            }
        }
    }

    /**
     * Save all arenas to the arenas.yml file.
     */
    public function saveAll(): void {
        $config = new Config(Habu::getInstance()->getDataFolder() . 'arenas.yml');

        foreach ($this->arenas as $arena) {
            $config->set($arena->getOriginalName(), $arena->getOriginalProperties());
        }

        try {
            $config->save();
        } catch (Exception $e) {
            Habu::getInstance()->getLogger()->error('Failed to save arenas: ' . $e->getMessage());
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
            if ($arena->getPrimaryKit() !== $kit->getName()) continue;

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
           Habu::getInstance()->getDataFolder() . 'backups/' . $arenaName,
           Server::getInstance()->getDataPath() . 'worlds/' . $worldName,
           $onComplete
       ));
    }

    public static function adapt(array &$properties): void {
        if (!isset($properties['first-position'])) {
            throw new RuntimeException('First position not set');
        }

        if (!is_array($properties['first-position'])) {
            throw new RuntimeException('Invalid first position data');
        }

        if (!isset($properties['second-position'])) {
            throw new RuntimeException('Second position not set');
        }

        if (!is_array($properties['second-position'])) {
            throw new RuntimeException('Invalid second position data');
        }

        $properties['first-position'] = ArenaProperties::deserializeVector($properties['first-position']);
        $properties['second-position'] = ArenaProperties::deserializeVector($properties['second-position']);

        if (!isset($properties['first-corner'])) {
            throw new RuntimeException('First corner not set');
        }

        if (!is_array($properties['first-corner'])) {
            throw new RuntimeException('Invalid first corner data');
        }

        if (!isset($properties['second-corner'])) {
            throw new RuntimeException('Second corner not set');
        }

        if (!is_array($properties['second-corner'])) {
            throw new RuntimeException('Invalid second corner data');
        }

        $properties['first-corner'] = ArenaProperties::deserializeVector($properties['first-corner']);
        $properties['second-corner'] = ArenaProperties::deserializeVector($properties['second-corner']);
    }
}