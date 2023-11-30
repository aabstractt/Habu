<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\ScalableArena;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use RuntimeException;

abstract class ScalableArenaSetup extends AbstractArenaSetup {

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
     * This method is called when the setup is started.
     * This is where you should set the player's inventory, gamemode, etc.
     *
     * @param Player $player
     */
    public function setup(Player $player): void {
        parent::setup($player);

        if ($this->startGridPoint === null) {
            throw new RuntimeException('Start grid point is not set');
        }

        // TODO: Paste schematic

        $player->teleport($this->startGridPoint);
    }

    /**
     * This method is called when the arena is created into the arena manager.
     * This is where you should set the arena's properties.
     *
     * @param AbstractArena $arena
     */
    public function submit(AbstractArena $arena): void {
        if (!$arena instanceof ScalableArena) {
            throw new RuntimeException('Arena must be an instance of ScalableArena');
        }

        if ($this->startGridPoint === null) {
            throw new RuntimeException('Start grid point is not set');
        }

        if ($this->spacingX <= 0) {
            throw new RuntimeException('Spacing X is not set');
        }

        if ($this->spacingZ <= 0) {
            throw new RuntimeException('Spacing Z is not set');
        }

        $arena->setStartGridPoint($this->startGridPoint);
        $arena->setSpacingX($this->spacingX);
        $arena->setSpacingZ($this->spacingZ);

        parent::submit($arena);
    }
}