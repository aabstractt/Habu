<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use pocketmine\math\Vector3;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use RuntimeException;

abstract class AbstractArena {

    /**
     * @param string         $name
     * @param ArenaSchematic $schematic
     * @param Vector3        $firstPosition
     * @param Vector3        $secondPosition
     * @param string[]       $duelTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly ArenaSchematic $schematic,
        private Vector3         $firstPosition,
        private Vector3         $secondPosition,
        private array           $duelTypes
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return ArenaSchematic
     */
    public function getSchematic(): ArenaSchematic {
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