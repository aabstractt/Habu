<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\ArenaProperties;
use pocketmine\math\Vector3;
use RuntimeException;

final class EventArenaProperties extends ArenaProperties {

    public const IDENTIFIER = 'Event';

    /**
     * @return Vector3
     */
    public function getFirstFightCorner(): Vector3 {
        return $this->properties['first-fight-position'] ?? throw new RuntimeException('First fight corner not set');
    }

    /**
     * @return Vector3
     */
    public function getSecondFightCorner(): Vector3 {
        return $this->properties['second-fight-position'] ?? throw new RuntimeException('Second fight corner not set');
    }

    public function adaptProperties(): void {
        parent::adaptProperties();

        if (!isset($this->properties['first-fight-position'])) {
            throw new RuntimeException('First fight corner not set');
        }

        if (!is_array($this->properties['first-fight-position'])) {
            throw new RuntimeException('Invalid first fight corner data');
        }

        if (!isset($this->properties['second-fight-position'])) {
            throw new RuntimeException('Second fight corner not set');
        }

        if (!is_array($this->properties['second-fight-position'])) {
            throw new RuntimeException('Invalid second fight corner data');
        }

        $this->properties['first-fight-position'] = self::deserializeVector($this->properties['first-fight-position']);
        $this->properties['second-fight-position'] = self::deserializeVector($this->properties['second-fight-position']);
    }
}