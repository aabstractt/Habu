<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use bitrule\practice\arena\impl\BridgeArena;
use bitrule\practice\arena\impl\DefaultArena;
use pocketmine\math\Vector3;
use RuntimeException;

abstract class AbstractArena {

    /**
     * @param string   $name
     * @param Vector3  $firstPosition
     * @param Vector3  $secondPosition
     * @param string[] $duelTypes
     */
    public function __construct(
        protected readonly string $name,
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

        $arena = match ($data['type']) {
            'normal' => DefaultArena::parse($name, $data),
            'bridge' => BridgeArena::parse($name, $data),
            default => throw new RuntimeException('Invalid arena type'),
        };
        $arena->setup($data);

        return $arena;
    }

    /**
     * @param string $name
     * @param string $type
     *
     * @return AbstractArena
     */
    public static function createEmpty(string $name, string $type): AbstractArena {
        $arena = match ($type) {
            'normal' => DefaultArena::parseEmpty($name),
            'bridge' => BridgeArena::parseEmpty($name),
            default => throw new RuntimeException('Invalid arena type'),
        };
        $arena->empty();

        return $arena;
    }

    /**
     * @param array $data
     *
     * @return Vector3
     */
    public static function deserializeVector(array $data): Vector3 {
        if (count($data) !== 3) {
            throw new RuntimeException('Invalid vector data');
        }

        return new Vector3($data[0], $data[1], $data[2]);
    }
}