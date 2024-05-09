<?php

declare(strict_types=1);

namespace bitrule\practice\duel\properties;

final class FireballFightProperties extends DuelProperties {

    /**
     * Marks the blue bed as destroyed.
     */
    public function setBlueBedDestroyed(): void {
        $this->properties['blue_bed_destroyed'] = true;
    }

    /**
     * @return bool
     */
    public function isBlueBedDestroyed(): bool {
        return $this->properties['blue_bed_destroyed'] ?? false;
    }

    /**
     * Marks the red bed as destroyed.
     */
    public function setRedBedDestroyed(): void {
        $this->properties['red_bed_destroyed'] = true;
    }

    /**
     * @return bool
     */
    public function isRedBedDestroyed(): bool {
        return $this->properties['red_bed_destroyed'] ?? false;
    }
}