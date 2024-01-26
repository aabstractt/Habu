<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\arena\ScalableArena;
use bitrule\practice\kit\Kit;
use bitrule\practice\Practice;
use bitrule\practice\arena\AbstractArena;
use bitrule\practice\task\ScaleArenaCopiesTask;
use Closure;
use Exception;
use pocketmine\block\tile\Sign;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\format\Chunk;
use pocketmine\world\Position;
use pocketmine\world\World;
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
            if (!in_array($kit->getName(), $arena->getDuelTypes(), true)) continue;
            if ($arena instanceof ScalableArena && !$arena->hasAvailableGrid()) continue;

            $arenas[] = $arena;
        }

        return $arenas[array_rand($arenas)] ?? null;
    }

    /**
     * @param ScalableArena $arena
     * @param int           $desiredCopies
     */
    public function scaleCopies(ScalableArena $arena, int $desiredCopies): void {
        if ($this->gridsBusy) {
            throw new RuntimeException('Grid building is busy');
        }

        $currentCopies = $arena->getTotalGrids();
        if ($currentCopies === $desiredCopies) {
            throw new RuntimeException('Grids are already scaled');
        }

        $this->gridsBusy = true;

        $saveWrapper = function (): void {
            $this->gridsBusy = false;
        };

        $amount = $desiredCopies - $currentCopies;
        if ($amount > 0) {
            $this->createGrids($arena, $currentCopies, $amount, $saveWrapper);
        } else {
            $this->deleteGrids($arena, $currentCopies, abs($amount), $saveWrapper);
        }
    }

    /**
     * @param ScalableArena   $arena
     * @param int             $currentCopies
     * @param int             $amount
     * @param Closure(): void $closure
     */
    public function createGrids(ScalableArena $arena, int $currentCopies, int $amount, Closure $closure): void {
        $arena->setTotalGrids($arena->getTotalGrids() + $amount);

        Practice::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(
            new ScaleArenaCopiesTask(
                $amount,
                fn (int $progressCount) => $arena->pasteModelArena(
                    $currentCopies + $progressCount,
                    fn () => $this->loadSpawns($arena->getWorld(), $arena->getFirstPosition(), $arena->getSecondPosition())
                ),
                $closure
            ),
            40,
            30
        );
    }

    /**
     * @param ScalableArena   $arena
     * @param int             $currentCopies
     * @param int             $amount
     * @param Closure(): void $closure
     */
    public function deleteGrids(ScalableArena $arena, int $currentCopies, int $amount, Closure $closure): void {
        $arena->setTotalGrids($arena->getTotalGrids() - $amount);

        Practice::getInstance()->getScheduler()->scheduleDelayedRepeatingTask(
            new ScaleArenaCopiesTask(
                $amount,
                fn (int $progressCount) => $arena->resetModelArena($currentCopies - $progressCount),
                $closure
            ),
            40,
            40
        );
    }

    /**
     * @param World    $world
     * @param Vector3 $min
     * @param Vector3 $max
     *
     * @return Location[]
     */
    private function loadSpawns(World $world, Vector3 $min, Vector3 $max): array {
        $spawns = [];

        $minX = $min->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $minZ = $min->getFloorZ() >> Chunk::COORD_BIT_SIZE;

        $maxX = $max->getFloorX() >> Chunk::COORD_BIT_SIZE;
        $maxZ = $max->getFloorZ() >> Chunk::COORD_BIT_SIZE;

        for ($x = $minX; $x < $maxX; $x += Chunk::COORD_BIT_SIZE) {
            for ($z = $minZ; $z < $maxZ; $z += Chunk::COORD_BIT_SIZE) {
                $chunk = $world->getChunk($x, $z);
                if ($chunk === null) continue;

                foreach ($chunk->getTiles() as $tile) {
                    if (!$tile instanceof Sign) continue;

                    $spawns[] = Location::fromObject($tile->getPosition(), $world);
                }
            }
        }

        return $spawns;
    }
}