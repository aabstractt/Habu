<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use pocketmine\math\Vector3;

final class FireballFightArena extends AbstractArena {

    public const NAME = 'fireball_fight';

    /**
     * @param string  $name
     * @param Vector3 $firstPosition
     * @param Vector3 $firstBedPosition
     * @param Vector3 $secondPosition
     * @param Vector3 $secondBedPosition
     * @param string  $knockbackProfile
     */
    public function __construct(
        string $name,
        Vector3 $firstPosition,
        private Vector3 $firstBedPosition,
        Vector3 $secondPosition,
        private Vector3 $secondBedPosition,
        string $knockbackProfile
    ) {
        parent::__construct($name, $firstPosition, $secondPosition, $knockbackProfile, [self::NAME]);
    }

    public function getFirstBedPosition(): Vector3 {
        return $this->firstBedPosition;
    }

    /**
     * This is the first bed position of the arena. (Red team)
     *
     * @param Vector3 $firstBedPosition
     */
    public function setFirstBedPosition(Vector3 $firstBedPosition): void {
        $this->firstBedPosition = $firstBedPosition;
    }

    /**
     * @return Vector3
     */
    public function getSecondBedPosition(): Vector3 {
        return $this->secondBedPosition;
    }

    /**
     * This is the second bed position of the arena. (Blue team)
     *
     * @param Vector3 $secondBedPosition
     */
    public function setSecondBedPosition(Vector3 $secondBedPosition): void {
        $this->secondBedPosition = $secondBedPosition;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(): array {
        return array_merge(
            parent::serialize(),
            [
                'type' => self::NAME,
                'first_bed_position' => self::serializeVector($this->firstBedPosition),
                'second_bed_position' => self::serializeVector($this->secondBedPosition)
            ]
        );
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return self
     */
    protected static function parse(string $name, array $data): self {
        return new self(
            $name,
            self::deserializeVector($data['first_position'] ?? []),
            self::deserializeVector($data['first_bed_position'] ?? []),
            self::deserializeVector($data['second_position'] ?? []),
            self::deserializeVector($data['second_bed_position'] ?? []),
            $data['knockback_profile'] ?? ''
        );
    }

    /**
     * @param string $name
     *
     * @return self
     */
    protected static function parseEmpty(string $name): self {
        return new self(
            $name,
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero(),
            'default'
        );
    }
}