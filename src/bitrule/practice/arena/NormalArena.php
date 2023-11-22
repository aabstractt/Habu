<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use pocketmine\math\Vector3;
use RuntimeException;

class NormalArena {

    /**
     * @param string  $name
     * @param string  $schematicName
     * @param Vector3 $firstPosition
     * @param Vector3 $secondPosition
     * @param array   $duelTypes
     */
    public function __construct(
        private readonly string $name,
        private readonly string $schematicName,
        private Vector3 $firstPosition,
        private Vector3 $secondPosition,
        private array $duelTypes
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
    public function getSchematicName(): string {
        return $this->schematicName;
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
     * @param string $duelType
     */
    public function addDuelType(string $duelType): void {
        $this->duelTypes[] = $duelType;
    }

    /**
     * @param string $type
     *
     * @return bool
     */
    public function isDuel(string $type): bool {
        return in_array($type, $this->duelTypes, true);
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return NormalArena
     */
    protected static function parseArena(string $name, array $data): NormalArena {
        throw new RuntimeException('');
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return NormalArena
     */
    public static function createFromArray(string $name, array $data): NormalArena {
        if (!isset($data['type'])) {
            throw new RuntimeException('Invalid offset "type"');
        }

        return match ($data['type']) {
            'normal' => NormalArena::parseArena($name, $data),
            default => throw new RuntimeException('Invalid arena type'),
        };
    }
}