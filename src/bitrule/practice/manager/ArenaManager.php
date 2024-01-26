<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\asyncio\FileCopyAsyncTask;
use bitrule\practice\kit\Kit;
use bitrule\practice\Practice;
use Closure;
use Exception;
use JsonException;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_filter;
use function array_rand;
use function count;
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
     * @throws JsonException
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
        $arenas = array_filter($this->arenas, fn(AbstractArena $arena) => $arena->hasKit($kit->getName()));
        if (count($arenas) === 0) return null;

        return $arenas[array_rand($arenas)] ?? null;
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