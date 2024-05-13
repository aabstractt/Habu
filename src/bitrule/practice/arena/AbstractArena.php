<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use bitrule\practice\arena\impl\BoxingArena;
use bitrule\practice\arena\impl\BridgeArena;
use bitrule\practice\arena\impl\DefaultArena;
use bitrule\practice\arena\impl\FireballFightArena;
use pocketmine\math\Vector3;
use RuntimeException;
use function count;
use function in_array;
use function strtolower;

abstract class AbstractArena {

    /**
     * @param string   $name
     * @param Vector3  $firstPosition
     * @param Vector3  $secondPosition
     * @param string[] $kits
     */
    public function __construct(
        protected readonly string $name,
        protected Vector3         $firstPosition,
        protected Vector3         $secondPosition,
        protected array $kits
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
    public function getKits(): array {
        return $this->kits;
    }

    /**
     * @param array $kits
     */
    public function setKits(array $kits): void {
        $this->kits = $kits;
    }

    /**
     * @param string $kitName
     */
    public function addKit(string $kitName): void {
        $this->kits[] = $kitName;
    }

    /**
     * @param string $kitName
     *
     * @return bool
     */
    public function hasKit(string $kitName): bool {
        return in_array($kitName, $this->kits, true);
    }

    /**
     * @param array $arenaData
     */
    public function setup(array $arenaData): void {
        if (!isset($arenaData['first_position'])) {
            throw new RuntimeException('Invalid offset "first_position"');
        }

        $this->firstPosition = self::deserializeVector($arenaData['first_position']);

        if (!isset($arenaData['second_position'])) {
            throw new RuntimeException('Invalid offset "second_position"');
        }

        $this->secondPosition = self::deserializeVector($arenaData['second_position']);

        if (!isset($arenaData['kits'])) {
            throw new RuntimeException('Invalid offset "kits"');
        }

        $this->kits = $arenaData['kits'];
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(): array {
        return [
        	'type' => 'normal',
        	'first_position' => self::serializeVector($this->firstPosition),
        	'second_position' => self::serializeVector($this->secondPosition),
        	'kits' => $this->kits
        ];
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return AbstractArena
     */
    public static function createFromArray(string $name, array $data): self {
        if (!isset($data['type'])) {
            throw new RuntimeException('Invalid offset "type"');
        }

        $arena = match ($data['type']) {
            'normal' => DefaultArena::parse($name, $data),
            'bridge' => BridgeArena::parse($name, $data),
            'boxing' => BoxingArena::parse($name, $data),
            FireballFightArena::NAME => FireballFightArena::parse($name, $data),
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
    public static function createEmpty(string $name, string $type): self {
        return match (strtolower($type)) {
            'normal' => DefaultArena::parseEmpty($name),
            'bridge' => BridgeArena::parseEmpty($name),
            'boxing' => BoxingArena::parseEmpty($name),
            FireballFightArena::NAME => FireballFightArena::parseEmpty($name),
            default => throw new RuntimeException('Invalid arena type'),
        };
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

    /**
     * @param Vector3 $vector
     *
     * @return int[]
     */
    public static function serializeVector(Vector3 $vector): array {
        return [$vector->getFloorX(), $vector->getFloorY(), $vector->getFloorZ()];
    }
}