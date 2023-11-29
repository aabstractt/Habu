<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\ScalableArena;
use pocketmine\math\Vector3;

final class DefaultArena extends ScalableArena {

    /**
     * @param string $name
     * @param array  $data
     *
     * @return DefaultArena
     */
    protected static function parse(string $name, array $data): DefaultArena {
        return new DefaultArena(
            $name,
            self::deserializeVector($data['firstPosition'] ?? []),
            self::deserializeVector($data['secondPosition'] ?? []),
            $data['duelTypes'] ?? []
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