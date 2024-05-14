<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\ArenaProperties;
use pocketmine\math\Vector3;
use RuntimeException;

final class FireballFightArenaProperties extends ArenaProperties {

    public const IDENTIFIER = 'Fireball Fight';

    public const TEAM_RED_ID = 0;
    public const TEAM_BLUE_ID = 1;

    /**
     * @return Vector3
     */
    public function getFirstBedPosition(): Vector3 {
        return $this->properties['first-bed-position'] ?? throw new RuntimeException('First bed position not set');
    }

    /**
     * @return Vector3
     */
    public function getSecondBedPosition(): Vector3 {
        return $this->properties['second-bed-position'] ?? throw new RuntimeException('Second bed position not set');
    }

    /**
     * Returns the original properties of the arena.
     *
     * @return array
     */
    public function getOriginalProperties(): array {
        $properties = parent::getOriginalProperties();
        $properties['first-bed-position'] = self::serializeVector($properties['first-bed-position']);
        $properties['second-bed-position'] = self::serializeVector($properties['second-bed-position']);

        return $properties;
    }

    /**
     * @param array $properties
     */
    public function setup(array $properties): void {
        if (!isset($properties['first-bed-position'])) {
            throw new RuntimeException('First bed position not set');
        }

        if (!isset($properties['second-bed-position'])) {
            throw new RuntimeException('Second bed position not set');
        }

        if (!$properties['first-bed-position'] instanceof Vector3) {
            $properties['first-bed-position'] = self::deserializeVector($properties['first-bed-position']);
        }

        if (!$properties['second-bed-position'] instanceof Vector3) {
            $properties['second-bed-position'] = self::deserializeVector($properties['second-bed-position']);
        }

        parent::setup($properties);
    }
}