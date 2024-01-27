<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

final class DefaultArenaSetup extends AbstractArenaSetup {

    public function increaseSpawnStep(): void {
        parent::increaseSpawnStep();

        if ($this->spawnStep < 2) return;

        $this->spawnStep = 0;
    }

    /**
     * Returns the type of arena setup.
     *
     * @return string
     */
    public function getType(): string {
        return 'normal';
    }
}