<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\ArenaSchematic;
use pocketmine\math\Vector3;

final class BridgeArena extends AbstractArena {

    /**
     * @param ArenaSchematic $schematic
     * @param Vector3        $firstPosition
     * @param Vector3        $secondPosition
     * @param Vector3        $firstPortal
     * @param Vector3        $secondPortal
     * @param string[]       $duelTypes
     */
    public function __construct(
        ArenaSchematic $schematic,
        Vector3 $firstPosition,
        Vector3 $secondPosition,
        private Vector3 $firstPortal,
        private Vector3 $secondPortal,
        array $duelTypes
    ) {
        parent::__construct($schematic, $firstPosition, $secondPosition, $duelTypes);
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

    /**
     * @param string $duelType
     */
    public function addDuelType(string $duelType): void {
        throw new \RuntimeException('This arena type cannot have duel types.');
    }

    /**
     * @param string $schematicName
     * @param array  $data
     *
     * @return BridgeArena
     */
    public static function parse(string $schematicName, array $data): BridgeArena {
        return new BridgeArena(
            ArenaSchematic::deserialize($schematicName, $data['schematic']),
            self::deserializeVector($data['firstPosition']),
            self::deserializeVector($data['secondPosition']),
            self::deserializeVector($data['firstPortal']),
            self::deserializeVector($data['secondPortal']),
            $data['duelTypes']
        );
    }

    /**
     * @param string $schematicName
     *
     * @return BridgeArena
     */
    protected static function parseEmpty(string $schematicName): BridgeArena {
        return new BridgeArena(
            new ArenaSchematic($schematicName, 0, 0, 0, Vector3::zero()),
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero(),
            []
        );
    }
}