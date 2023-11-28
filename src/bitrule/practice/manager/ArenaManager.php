<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\arena\ArenaSchematic;
use bitrule\practice\Practice;
use bitrule\practice\arena\AbstractArena;
use Closure;
use Exception;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;
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
     * @param ArenaSchematic     $schematic
     * @param int                $desiredCopies
     * @param Closure(int): void $closure
     */
    public function scaleCopies(ArenaSchematic $schematic, int $desiredCopies, Closure $closure): void {
        if ($this->gridsBusy) {
            $closure(-2);

            return;
        }

        $currentCopies = $schematic->getGridIndex();
        if ($currentCopies === $desiredCopies) {
            $closure(-1);

            return;
        }

        $this->gridsBusy = true;

        $saveWrapper = function (): void {
            $this->gridsBusy = false;
        };

        if ($currentCopies > $desiredCopies) {
            $this->deleteGrids($schematic, $currentCopies, $currentCopies - $desiredCopies, $saveWrapper);
        } else {
            $this->createGrids($schematic, $currentCopies, $desiredCopies - $currentCopies, $saveWrapper);
        }
    }

    /**
     * @param ArenaSchematic  $schematic
     * @param int             $currentCopies
     * @param int             $amount
     * @param Closure(): void $closure
     */
    public function createGrids(ArenaSchematic $schematic, int $currentCopies, int $amount, Closure $closure): void {
        $schematic->setGridIndex($schematic->getGridIndex() + $amount);

        $pasted = 0;
        Practice::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(
            new ClosureTask(function () use (&$pasted, $currentCopies, $closure, $schematic, $amount): void {
                $pasted++;

                if ($pasted > $amount) {
                    $closure();

                    throw new CancelTaskException();
                }

                $schematic->pasteModelArena($currentCopies + $pasted);
            }),
            40,
            40
        );
    }

    /**
     * @param ArenaSchematic  $schematic
     * @param int             $currentCopies
     * @param int             $amount
     * @param Closure(): void $closure
     */
    public function deleteGrids(ArenaSchematic $schematic, int $currentCopies, int $amount, Closure $closure): void {
        $schematic->setGridIndex($schematic->getGridIndex() - $amount);

        $closure();
    }
}