<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\ArenaProperties;
use pocketmine\math\Vector3;
use RuntimeException;

final class BridgeArenaProperties extends ArenaProperties {

    public const IDENTIFIER = 'Bridge';

    /**
     * The first portal is for the Red Team
     *
     * @return Vector3
     */
    public function getFirstPortal(): Vector3 {
        return $this->properties['first-portal'] ?? throw new RuntimeException('First portal not set');
    }

    /**
     * The second portal is for the Blue Team
     *
     * @return Vector3
     */
    public function getSecondPortal(): Vector3 {
        return $this->properties['second-portal'] ?? throw new RuntimeException('Second portal not set');
    }
}