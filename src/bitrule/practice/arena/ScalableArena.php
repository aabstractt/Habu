<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use Closure;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;

/**
 * Class ScalableArena is an abstract class that represents a scalable arena.
 */
abstract class ScalableArena extends AbstractArena {

    /**
     * Start grid point of the schematic.
     *
     * @var Vector3|null
     */
    protected ?Vector3 $startGridPoint = null;
    /**
     * Spacing X of the schematic.
     * @var int
     */
    protected int $spacingX = 0;
    /**
     * Spacing Z of the schematic.
     * @var int
     */
    protected int $spacingZ = 0;
    /**
     * Grid index of the schematic.
     * @var int
     */
    protected int $totalGrids = 0;
    /**
     * Available grids of the schematic.
     * @var int[]
     */
    protected array $availableGrids = [];

    /**
     * @return World
     */
    public function getWorld(): World {
        throw new RuntimeException('Not implemented');
    }

    /**
     * @return Vector3
     */
    public function getStartGridPoint(): Vector3 {
        return $this->startGridPoint ?? throw new RuntimeException('Start grid point is not set');
    }

    /**
     * @param Vector3|null $startGridPoint
     */
    public function setStartGridPoint(?Vector3 $startGridPoint): void {
        $this->startGridPoint = $startGridPoint;
    }

    /**
     * @return int
     */
    public function getSpacingX(): int {
        return $this->spacingX;
    }

    /**
     * @param int $spacingX
     */
    public function setSpacingX(int $spacingX): void {
        $this->spacingX = $spacingX;
    }

    /**
     * @return int
     */
    public function getSpacingZ(): int {
        return $this->spacingZ;
    }

    /**
     * @param int $spacingZ
     */
    public function setSpacingZ(int $spacingZ): void {
        $this->spacingZ = $spacingZ;
    }

    /**
     * @return int
     */
    public function getTotalGrids(): int {
        return $this->totalGrids;
    }

    /**
     * @param int $totalGrids
     */
    public function setTotalGrids(int $totalGrids): void {
        $this->totalGrids = $totalGrids;
    }

    /**
     * @param int $index
     *
     * @return Vector3
     */
    protected function getGridVector(int $index): Vector3 {
        if ($this->startGridPoint === null) {
            throw new RuntimeException('Start grid point is not set');
        }

        return $this->startGridPoint->add(
            $this->spacingX * $index,
            0,
            $this->spacingZ * $index
        );
    }

    /**
     * @param int     $id
     * @param Closure(): Location[] $spawnsWrapper
     */
    public function loadModelArena(int $id, Closure $spawnsWrapper): void {
        $location =

        $pasteWrapper = function (): void {

        };
    }

    /**
     * @param int     $index
     * @param Closure(): Position[] $closure
     * @param bool    $force
     */
    public function pasteModelArena(int $index, Closure $closure, bool $force = false): void {
        if (in_array($index, $this->availableGrids, true)) {
            throw new RuntimeException('Grid ' . $index . ' is already available');
        }

        $this->availableGrids[] = $index;

        $pasteAt = $this->getGridVector($index);

        // TODO: Paste the schematic here
    }

    /**
     * @param int $index
     */
    public function resetModelArena(int $index): void {
        // TODO: Implement resetModelArena() method.
    }

    /**
     * @return bool
     */
    public function hasAvailableGrid(): bool {
        return count($this->availableGrids) > 0;
    }

    /**
     * @return int
     */
    public function getAvailableGrid(): int {
        $available = $this->availableGrids[array_rand($this->availableGrids)];
        if ($available === null || $available <= 0) {
            throw new RuntimeException('No grids available');
        }

        return $available;
    }

    /**
     * @param int $index
     */
    public function removeAvailableGrid(int $index): void {
        $key = array_search($index, $this->availableGrids, true);
        if ($key === false) {
            throw new RuntimeException('Grid ' . $index . ' is not available');
        }

        unset($this->availableGrids[$key]);
    }

    /**
     * @param array $data
     */
    public function setup(array $data): void {
        if (!isset($data['start_point'])) {
            throw new RuntimeException('Missing start_point in ' . $this->name);
        }

        $this->startGridPoint = AbstractArena::deserializeVector($data['startGridPoint']);

        if (!isset($data['spacing_x'])) {
            throw new RuntimeException('Missing spacing_x in ' . $this->name);
        }

        $this->spacingX = intval($data['spacing_x']);

        if (!isset($data['spacing_z'])) {
            throw new RuntimeException('Missing spacing_z in ' . $this->name);
        }

        $this->spacingZ = intval($data['spacing_z']);

        if (!isset($data['total_grids'])) {
            throw new RuntimeException('Missing total_grids in ' . $this->name);
        }

        $this->totalGrids = intval($data['total_grids']);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(): array {
        return array_merge(
            parent::serialize(),
            [
                'start_point' => $this->startGridPoint !== null ? self::serializeVector($this->startGridPoint) : [],
                'spacing_x' => $this->spacingX,
                'spacing_z' => $this->spacingZ,
                'total_grids' => $this->totalGrids
            ]
        );
    }
}