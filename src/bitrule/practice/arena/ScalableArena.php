<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use pocketmine\math\Vector3;
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
    protected int $gridIndex = 0;
    /**
     * Available grids of the schematic.
     * @var int[]
     */
    protected array $availableGrids = [];

    /**
     * @return Vector3
     */
    public function getStartGridPoint(): Vector3 {
        return $this->startGridPoint ?? throw new RuntimeException('Start grid point is not set');
    }

    /**
     * @return int
     */
    public function getGridIndex(): int {
        return $this->gridIndex;
    }

    /**
     * @param int $gridIndex
     */
    public function setGridIndex(int $gridIndex): void {
        $this->gridIndex = $gridIndex;
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
     * @param int  $index
     * @param bool $force
     */
    public function pasteModelArena(int $index, bool $force = false): void {
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
        return $this->availableGrids[array_rand($this->availableGrids)];
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
     * Initialize the scalable options.
     */
    public function empty(): void {
        $this->startGridPoint = Vector3::zero();
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

        if (!isset($data['grid_index'])) {
            throw new RuntimeException('Missing grid_index in ' . $this->name);
        }

        $this->gridIndex = intval($data['grid_index']);
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
                'grid_index' => $this->gridIndex
            ]
        );
    }
}