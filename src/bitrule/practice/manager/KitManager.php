<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\kit\Kit;
use pocketmine\utils\SingletonTrait;

final class KitManager {
    use SingletonTrait;

    /**
     * @param string $name
     *
     * @return Kit|null
     */
    public function getKit(string $name): ?Kit {
        return null;
    }
}