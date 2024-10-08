<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

final class BridgeArenaSetup extends AbstractArenaSetup {

    /**
     * Returns the type of arena setup.
     *
     * @return string
     */
    public function getType(): string {
        return 'bridge';
    }
}