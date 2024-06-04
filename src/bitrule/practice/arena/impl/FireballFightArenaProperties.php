<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\ArenaProperties;
use pocketmine\math\Vector3;
use RuntimeException;
use function is_array;

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
     * Adapts the properties of the arena.
     * This method should be called after the properties have been set.
     */
    public function adaptProperties(): void {
        parent::adaptProperties();

        if (!isset($this->properties['first-bed-position'])) {
            throw new RuntimeException('First bed position not set');
        }

        if (!is_array($this->properties['first-bed-position'])) {
            throw new RuntimeException('Invalid first bed position data');
        }

        if (!isset($this->properties['second-bed-position'])) {
            throw new RuntimeException('Second bed position not set');
        }

        if (!is_array($this->properties['second-bed-position'])) {
            throw new RuntimeException('Invalid second bed position data');
        }

        $this->properties['first-bed-position'] = self::deserializeVector($this->properties['first-bed-position']);
        $this->properties['second-bed-position'] = self::deserializeVector($this->properties['second-bed-position']);
    }
}