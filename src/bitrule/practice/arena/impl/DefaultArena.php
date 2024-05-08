<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use pocketmine\math\Vector3;

/**
 * Class DefaultArena is a default arena.
 */
final class DefaultArena extends AbstractArena {

    /**
     * @param string $name
     * @param array  $data
     *
     * @return DefaultArena
     */
    protected static function parse(string $name, array $data): self {
        return new self(
            $name,
            self::deserializeVector($data['first_position'] ?? []),
            self::deserializeVector($data['second_position'] ?? []),
            $data['knockback_profile'] ?? 'default',
            $data['kits'] ?? []
        );
    }

    /**
     * @param string $name
     *
     * @return DefaultArena
     */
    protected static function parseEmpty(string $name): self {
        return new self(
            $name,
            Vector3::zero(),
            Vector3::zero(),
            'default',
            []
        );
    }
}