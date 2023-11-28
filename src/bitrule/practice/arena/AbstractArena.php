<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use pocketmine\math\Vector3;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use RuntimeException;

abstract class AbstractArena {

    /**
     * @param string   $name
     * @param string   $schematic
     * @param Vector3  $firstPosition
     * @param Vector3  $secondPosition
     * @param string[] $duelTypes
     * @param array    $grids
     */
    public function __construct(
        private readonly string $name,
        private readonly string $schematic,
        private Vector3         $firstPosition,
        private Vector3         $secondPosition,
        private array           $duelTypes,
        private array $grids
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getSchematic(): string {
        return $this->schematic;
    }

    /**
     * @return Vector3
     */
    public function getFirstPosition(): Vector3 {
        return $this->firstPosition;
    }

    /**
     * @param Vector3 $firstPosition
     */
    public function setFirstPosition(Vector3 $firstPosition): void {
        $this->firstPosition = $firstPosition;
    }

    /**
     * @return Vector3
     */
    public function getSecondPosition(): Vector3 {
        return $this->secondPosition;
    }

    /**
     * @param Vector3 $secondPosition
     */
    public function setSecondPosition(Vector3 $secondPosition): void {
        $this->secondPosition = $secondPosition;
    }

    /**
     * @return array
     */
    public function getDuelTypes(): array {
        return $this->duelTypes;
    }

    /**
     * @param string $duelType
     */
    public function addDuelType(string $duelType): void {
        $this->duelTypes[] = $duelType;
    }

    /**
     * @param string $duelType
     *
     * @return bool
     */
    public function hasDuelType(string $duelType): bool {
        return in_array($duelType, $this->duelTypes, true);
    }

    /**
     * @return array
     */
    public function getGrids(): array {
        return $this->grids;
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return AbstractArena
     */
    public static function createFromArray(string $name, array $data): AbstractArena {
        if (!isset($data['type'])) {
            throw new RuntimeException('Invalid offset "type"');
        }

        return match ($data['type']) {
            'normal' => DefaultArena::parse($name, $data),
            'bridge' => BridgeArena::parse($name, $data),
            default => throw new RuntimeException('Invalid arena type'),
        };
    }
}