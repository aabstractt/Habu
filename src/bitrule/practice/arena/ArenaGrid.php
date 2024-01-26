<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use RuntimeException;

final class ArenaGrid {

    /**
     * @param int     $id
     * @param Vector3 $center
     * @param Location[]   $spawns
     */
    public function __construct(
        private readonly int     $id,
        private readonly Vector3 $center,
        private readonly array $spawns = []
    ) {}

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return Vector3
     */
    public function getCenter(): Vector3 {
        return $this->center;
    }

    /**
     * @return Location
     */
    public function getFirstSpawn(): Location {
        return $this->spawns[0] ?? throw new RuntimeException('No spawns');
    }

    /**
     * @return Location
     */
    public function getSecondSpawn(): Location {
        return $this->spawns[1] ?? throw new RuntimeException('No spawns');
    }
}