<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\kit\Kit;
use pocketmine\utils\SingletonTrait;

final class KitManager {
    use SingletonTrait;

    /** @var array<string, Kit> */
    private array $kits = [];

    /**
     * @param string $name
     *
     * @return Kit|null
     */
    public function getKit(string $name): ?Kit {
        return null;
    }

    /**
     * @return array<string, Kit>
     */
    public function getKits(): array {
        return $this->kits;
    }
}