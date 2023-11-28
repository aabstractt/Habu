<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\Practice;
use bitrule\practice\arena\AbstractArena;
use Exception;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class ArenaManager {
    use SingletonTrait;

    public static ?Vector3 $STARTING_VECTOR = null;

    /** @var array<string, AbstractArena> */
    private array $arenas = [];

    public function init(): void {
        self::$STARTING_VECTOR = new Vector3(100, 80, 100);

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

    /**
     * @param string $duelType
     *
     * @return AbstractArena|null
     */
    public function getRandomArena(string $duelType): ?AbstractArena {
        $arenas = [];

        foreach ($this->arenas as $arena) {
            if (!in_array($duelType, $arena->getDuelTypes(), true)) continue;

            $arenas[] = $arena;
        }

        return $arenas[array_rand($arenas)] ?? null;
    }
}