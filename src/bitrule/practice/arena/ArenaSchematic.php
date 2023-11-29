<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use pocketmine\math\Vector3;
use RuntimeException;

final class ArenaSchematic {

    /** @var int[] */
    private array $availableGrids = [];

    /**
     * @param string  $name
     * @param int     $gridIndex
     * @param int     $spacingX
     * @param int     $spacingZ
     * @param Vector3 $startGridPoint
     */
    public function __construct(
        private readonly string $name,
        private readonly Vector3 $startGridPoint,
        private int $gridIndex = 0,
        private readonly int $spacingX = 0,
        private readonly int $spacingZ = 0
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
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
     * @return int
     */
    public function getSpacingX(): int {
        return $this->spacingX;
    }

    /**
     * @return int
     */
    public function getSpacingZ(): int {
        return $this->spacingZ;
    }

    /**
     * @return Vector3
     */
    public function getStartGridPoint(): Vector3 {
        return $this->startGridPoint;
    }

    /**
     * @param int $index
     *
     * @return Vector3
     */
    public function toGridLocation(int $index): Vector3 {
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
     * This method is called when the arena is loaded.
     * It should be used to load the schematic.
     * Load the available grids.
     * And paste all model arenas.
     */
    public function setup(): void {
        throw new RuntimeException('Not implemented');
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return ArenaSchematic
     */
    public static function deserialize(string $name, array $data): ArenaSchematic {
        if (!isset($data['gridIndex'], $data['spacingX'], $data['spacingZ'], $data['startGridPoint'])) {
            throw new RuntimeException('Invalid offset');
        }

        return new ArenaSchematic(
            $name,
            AbstractArena::deserializeVector($data['startGridPoint']),
            $data['gridIndex'],
            $data['spacingX'],
            $data['spacingZ']
        );
    }
}