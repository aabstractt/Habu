<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use RuntimeException;

final class DefaultArena extends AbstractArena {

    /**
     * @param string $schematicName
     * @param array  $data
     *
     * @return DefaultArena
     */
    protected static function parse(string $schematicName, array $data): DefaultArena {
        throw new RuntimeException('Not implemented');
    }
}