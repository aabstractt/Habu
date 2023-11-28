<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use bitrule\practice\manager\ArenaManager;
use pocketmine\math\Vector3;
use RuntimeException;

final class ArenaGrid {

    /**
     * @param int $spacingX
     * @param int $spacingZ
     * @param int $gridIndex
     */
    public function __construct(
        private readonly int $spacingX,
        private readonly int $spacingZ,
        private readonly int $gridIndex
    ) {}

    public function getLocation(): Vector3 {
        $vector = ArenaManager::$STARTING_VECTOR;
        if ($vector === null) {
            throw new RuntimeException("Starting vector is not set");
        }

        return new Vector3(
            $vector->getX() - $this->spacingX * $this->gridIndex,
            $vector->getY(),
            $vector->getZ() - $this->spacingZ * $this->gridIndex
        );
    }

    public function pasteModelArena(): void {

    }

    public function removeModelArena(): void {

    }
}