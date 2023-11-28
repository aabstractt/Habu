<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use pocketmine\math\Vector3;

final class BridgeArena extends AbstractArena {

    /**
     * @param string  $name
     * @param string  $schematic
     * @param Vector3 $firstPosition
     * @param Vector3 $secondPosition
     * @param Vector3 $firstPortal
     * @param Vector3 $secondPortal
     * @param string[]   $duelTypes
     */
    public function __construct(
        string $name,
        string $schematic,
        Vector3 $firstPosition,
        Vector3 $secondPosition,
        private Vector3 $firstPortal,
        private Vector3 $secondPortal,
        array $duelTypes
    ) {
        parent::__construct($name, $schematic, $firstPosition, $secondPosition, $duelTypes);
    }

    /**
     * @return Vector3
     */
    public function getFirstPortal(): Vector3 {
        return $this->firstPortal;
    }

    /**
     * @param Vector3 $firstPortal
     */
    public function setFirstPortal(Vector3 $firstPortal): void {
        $this->firstPortal = $firstPortal;
    }

    /**
     * @return Vector3
     */
    public function getSecondPortal(): Vector3 {
        return $this->secondPortal;
    }

    /**
     * @param Vector3 $secondPortal
     */
    public function setSecondPortal(Vector3 $secondPortal): void {
        $this->secondPortal = $secondPortal;
    }

    public function addDuelType(string $duelType): void {
        throw new \RuntimeException('This arena type cannot have duel types.');
    }

    public static function parse(string $schematicName, array $data): BridgeArena {
        throw new \RuntimeException('This arena type cannot be parsed.');
    }
}