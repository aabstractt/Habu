<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use bitrule\practice\arena\impl\BedFightArenaProperties;
use bitrule\practice\arena\impl\BridgeArenaProperties;
use bitrule\practice\arena\impl\DefaultArenaProperties;
use bitrule\practice\arena\impl\EventArenaProperties;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use RuntimeException;
use function count;
use function is_array;
use function strtolower;

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
     * @param array $properties
     */
    public function setProperties(array $properties): void {
        $this->properties = $properties;
    }

    /**
     * @return string
     */
    public function getOriginalName(): string {
        return $this->originalName;
    }

    /**
     * @return string
     */
    public function getPrimaryKit(): string {
        return $this->properties['primary-kit'] ?? throw new RuntimeException('Primary kit not set');
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
     * @return Vector3
     */
    public function getFirstCorner(): Vector3 {
        return $this->properties['first-corner'] ?? throw new RuntimeException('First corner not set');
    }

    /**
     * @return Vector3
     */
    public function getSecondCorner(): Vector3 {
        return $this->properties['second-corner'] ?? throw new RuntimeException('Second corner not set');
    }

    /**
     * Returns the original properties of the arena.
     *
     * @return array
     */
    public function getOriginalProperties(): array {
        $properties = [];

        foreach ($this->properties as $propertyName => $propertyValue) {
            $properties[$propertyName] = $propertyValue instanceof Location ? self::serializeVector($propertyValue) : $propertyValue;
        }

        return $properties;
    }

    /**
     * Adapts the properties of the arena.
     * This method should be called after the properties have been set.
     */
    public function adaptProperties(): void {
        if (count($this->properties) === 0) {
            throw new RuntimeException('Properties not set');
        }

        if (!isset($this->properties['first-position'])) {
            throw new RuntimeException('First position not set');
        }

        if (!is_array($this->properties['first-position'])) {
            throw new RuntimeException('Invalid first position data');
        }

        if (!isset($this->properties['second-position'])) {
            throw new RuntimeException('Second position not set');
        }

        if (!is_array($this->properties['second-position'])) {
            throw new RuntimeException('Invalid second position data');
        }

        $this->properties['first-position'] = self::deserializeVector($this->properties['first-position']);
        $this->properties['second-position'] = self::deserializeVector($this->properties['second-position']);

        if (!isset($this->properties['first-corner'])) {
            throw new RuntimeException('First corner not set');
        }

        if (!is_array($this->properties['first-corner'])) {
            throw new RuntimeException('Invalid first corner data');
        }

        if (!isset($this->properties['second-corner'])) {
            throw new RuntimeException('Second corner not set');
        }

        if (!is_array($this->properties['second-corner'])) {
            throw new RuntimeException('Invalid second corner data');
        }

        $this->properties['first-corner'] = self::deserializeVector($this->properties['first-corner']);
        $this->properties['second-corner'] = self::deserializeVector($this->properties['second-corner']);
    }

    /**
     * @param string $arenaName
     * @param string $type
     *
     * @return self
     */
    public static function parse(string $arenaName, string $type): self {
        return match (strtolower($type)) {
            'default' => new DefaultArenaProperties($arenaName),
            strtolower(BedFightArenaProperties::IDENTIFIER) => new BedFightArenaProperties($arenaName),
            strtolower(BridgeArenaProperties::IDENTIFIER) => new BridgeArenaProperties($arenaName),
            strtolower(EventArenaProperties::IDENTIFIER) => new EventArenaProperties($arenaName),
            default => throw new RuntimeException('Invalid arena type: ' . $type)
        };
    }

    /**
     * @param string $kitName
     *
     * @return string
     */
    public static function getArenaTypeByKit(string $kitName): string {
        return match (strtolower($kitName)) {
            'bridge' => BridgeArenaProperties::IDENTIFIER,
            strtolower(BedFightArenaProperties::IDENTIFIER), 'fireball fight' => BedFightArenaProperties::IDENTIFIER,
            'sumoevent' => EventArenaProperties::IDENTIFIER,
            default => 'default'
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
     * @return numeric[]
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