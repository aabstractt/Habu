<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use pocketmine\math\Vector3;

final class DefaultArena extends AbstractArena {

    /**
     * @param string $name
     * @param array  $data
     *
     * @return DefaultArena
     */
    protected static function parse(string $name, array $data): DefaultArena {
        return new DefaultArena(
            $name,
            self::deserializeVector($data['first_position'] ?? []),
            self::deserializeVector($data['second_position'] ?? []),
            $data['kits'] ?? []
        );
    }

    /**
     * @param string $name
     *
     * @return DefaultArena
     */
    protected static function parseEmpty(string $name): DefaultArena {
        return new DefaultArena(
            $name,
            Vector3::zero(),
            Vector3::zero(),
            []
        );
    }
}