<?php

declare(strict_types=1);

namespace bitrule\practice\arena;

use RuntimeException;

final class DefaultArena extends AbstractArena {

    /**
     * @param string $name
     * @param array  $data
     *
     * @return AbstractArena
     */
    protected static function parse(string $name, array $data): AbstractArena {
        throw new RuntimeException('Not implemented');
    }
}