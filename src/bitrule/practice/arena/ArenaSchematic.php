<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use pocketmine\math\Vector3;

final class ArenaSchematic {

    private array $gridsUsed = [];

    /**
     * @param string  $name
     * @param int     $spacingX
     * @param int     $spacingZ
     * @param Vector3 $startGridPoint
     */
    public function __construct(
        private readonly string $name,
        private int $gridIndex,
        private readonly int $spacingX,
        private readonly int $spacingZ,
        private readonly Vector3 $startGridPoint
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
     * @param int $index
     */
    public function pasteModelArena(int $index): void {
        if (in_array($index, $this->gridsUsed, true)) {
            throw new \RuntimeException('Grid ' . $index . ' is already used');
        }

        $this->gridsUsed[] = $index;
    }

    /**
     * @param int $index
     */
    public function resetModelArena(int $index): void {
        if (!in_array($index, $this->gridsUsed, true)) {
            throw new \RuntimeException('Grid ' . $index . ' is not used');
        }

        unset($this->gridsUsed[array_search($index, $this->gridsUsed, true)]);
    }
}