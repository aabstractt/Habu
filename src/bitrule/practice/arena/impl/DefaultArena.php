<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use RuntimeException;

final class DefaultArena extends AbstractArena {

    /**
     * @param string $name
     * @param array  $data
     *
     * @return DefaultArena
     */
    protected static function parse(string $name, array $data): DefaultArena {
        throw new RuntimeException('Not implemented');
    }
}