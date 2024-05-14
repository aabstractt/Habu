<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use bitrule\practice\arena\impl\BridgeArenaProperties;
use bitrule\practice\arena\impl\DefaultArenaProperties;
use bitrule\practice\arena\impl\FireballFightArenaProperties;
use pocketmine\entity\Location;
use RuntimeException;
use function count;

abstract class ArenaProperties {

    /**
     * @param string $originalName
     * @param array  $properties
     */
    public function __construct(
        protected string $originalName,
        protected array $properties = []
    ) {}

    /**
     * @return string
     */
    public function getOriginalName(): string {
        return $this->originalName;
    }

    /**
     * @return string
     */
    public function getArenaType(): string {
        return $this->properties['type'];
    }

    /**
     * @return Location
     */
    public function getFirstPosition(): Location {
        return $this->properties['first-position'] ?? throw new RuntimeException('First position not set');
    }

    /**
     * @return Location
     */
    public function getSecondPosition(): Location {
        return $this->properties['second-position'] ?? throw new RuntimeException('Second position not set');
    }

    /**
     * Returns the original properties of the arena.
     *
     * @return array
     */
    public function getOriginalProperties(): array {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    public function setup(array $properties): void {
        if (!isset($properties['name'])) {
            throw new RuntimeException('Name not set');
        }

        if (!isset($properties['first-position'])) {
            throw new RuntimeException('First position not set');
        }

        if (!isset($properties['second-position'])) {
            throw new RuntimeException('Second position not set');
        }

        if (!$properties['first-position'] instanceof Location) {
            $properties['first-position'] = self::deserializeVector($properties['first-position']);
        }

        if (!$properties['second-position'] instanceof Location) {
            $properties['second-position'] = self::deserializeVector($properties['second-position']);
        }

        $this->properties = $properties;
    }

    /**
     * @param string $arenaName
     * @param array  $properties
     *
     * @return self
     */
    public static function parse(string $arenaName, array $properties): self {
        if (!isset($properties['type'])) {
            throw new RuntimeException('Type not set');
        }

        return match ($properties['type']) {
            'default' => new DefaultArenaProperties($arenaName),
            FireballFightArenaProperties::IDENTIFIER => new FireballFightArenaProperties($arenaName),
            BridgeArenaProperties::IDENTIFIER => new BridgeArenaProperties($arenaName),
            default => throw new RuntimeException('Invalid arena type')
        };
    }

    /**
     * @param array $data
     *
     * @return Location
     */
    public static function deserializeVector(array $data): Location {
        if (count($data) !== 5) {
            throw new RuntimeException('Invalid vector data');
        }

        return new Location(
            $data[0],
            $data[1],
            $data[2],
            null,
            $data[3],
            $data[4]
        );
    }

    /**
     * @param Location $location
     *
     * @return int[]
     */
    public static function serializeVector(Location $location): array {
        return [
        	$location->getFloorX(),
        	$location->getFloorY(),
        	$location->getFloorZ(),
        	$location->getYaw(),
        	$location->getPitch()
        ];
    }
}