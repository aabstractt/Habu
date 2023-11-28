<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\player\DuelPlayer;
use pocketmine\utils\SingletonTrait;

final class PlayerManager {
    use SingletonTrait;

    /** @var array<string, DuelPlayer> */
    private array $duelPlayers = [];

    /**
     * @param string $xuid
     *
     * @return DuelPlayer|null
     */
    public function getDuelPlayer(string $xuid): ?DuelPlayer {
        return $this->duelPlayers[$xuid] ?? null;
    }
}