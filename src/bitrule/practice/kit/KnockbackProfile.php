<?php

declare(strict_types=1);

namespace bitrule\practice\kit;

final class KnockbackProfile {

    /**
     * @param string $name
     * @param float  $horizontal
     * @param float  $vertical
     * @param float  $highestLimit
     * @param int    $hitDelay
     */
    public function __construct(
        private readonly string $name,
        private float $horizontal,
        private float $vertical,
        private float $highestLimit,
        private int $hitDelay,
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * The horizontal value of the knockback profile.
     *
     * @return float
     */
    public function getHorizontal(): float {
        return $this->horizontal;
    }

    /**
     * @param float $horizontal
     */
    public function setHorizontal(float $horizontal): void {
        $this->horizontal = $horizontal;
    }

    /**
     * @return float
     */
    public function getVertical(): float {
        return $this->vertical;
    }

    /**
     * @param float $vertical
     */
    public function setVertical(float $vertical): void {
        $this->vertical = $vertical;
    }

    /**
     * The highest limit of the knockback profile.
     * When the knockback value is higher than this value, it will be set to this value.
     *
     * @return float
     */
    public function getHighestLimit(): float {
        return $this->highestLimit;
    }

    /**
     * @param float $highestLimit
     */
    public function setHighestLimit(float $highestLimit): void {
        $this->highestLimit = $highestLimit;
    }

    /**
     * @return int
     */
    public function getHitDelay(): int {
        return $this->hitDelay;
    }

    /**
     * @param int $hitDelay
     */
    public function setHitDelay(int $hitDelay): void {
        $this->hitDelay = $hitDelay;
    }

    /**
     * Creates an empty knockback profile with the given name.
     *
     * @param string $name
     *
     * @return self
     */
    public static function empty(string $name): self {
        return new self($name, 0.0, 0.0, 0.0, -1);
    }
}