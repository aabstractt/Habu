<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\Practice;
use bitrule\practice\arena\AbstractArena;
use Closure;
use Exception;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class ArenaManager {
    use SingletonTrait;

    /** @var array<string, AbstractArena> */
    private array $arenas = [];

    private bool $gridsBusy = false;

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

    /**
     * @param AbstractArena      $arena
     * @param int                $desiredCopies
     * @param Closure(int): void $closure
     */
    public function scaleCopies(AbstractArena $arena, int $desiredCopies, Closure $closure): void {
        if ($this->gridsBusy) {
            $closure(-2);

            return;
        }

        $currentCopies = count($arena->getGrids());
        if ($currentCopies === $desiredCopies) {
            $closure(-1);

            return;
        }

        $this->gridsBusy = true;

        $saveWrapper = function (): void {
            $this->gridsBusy = false;
        };

        if ($currentCopies > $desiredCopies) {
            $this->deleteGrids($arena, $currentCopies - $desiredCopies, $saveWrapper);
        } else {
            $this->createGrids($arena, $desiredCopies - $currentCopies, $saveWrapper);
        }
    }

    /**
     * @param AbstractArena $arena
     * @param int           $amount
     * @param Closure(): void       $closure
     */
    public function createGrids(AbstractArena $arena, int $amount, Closure $closure): void {

    }

    /**
     * @param AbstractArena $arena
     * @param int           $amount
     * @param Closure(): void       $closure
     */
    public function deleteGrids(AbstractArena $arena, int $amount, Closure $closure): void {

    }
}