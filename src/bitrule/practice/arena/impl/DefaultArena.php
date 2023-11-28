<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\ArenaSchematic;
use pocketmine\math\Vector3;
use RuntimeException;

final class DefaultArena extends AbstractArena {

    /**
     * @param string $schematicName
     * @param array  $data
     *
     * @return DefaultArena
     */
    protected static function parse(string $schematicName, array $data): DefaultArena {
        return new DefaultArena(
            ArenaSchematic::deserialize($schematicName, $data['schematic'] ?? []),
            self::deserializeVector($data['firstPosition'] ?? []),
            self::deserializeVector($data['secondPosition'] ?? []),
            $data['duelTypes'] ?? []
        );
    }

    /**
     * @param string $schematicName
     *
     * @return DefaultArena
     */
    protected static function parseEmpty(string $schematicName): DefaultArena {
        return new DefaultArena(
            new ArenaSchematic($schematicName, Vector3::zero()),
            Vector3::zero(),
            Vector3::zero(),
            []
        );
    }
}