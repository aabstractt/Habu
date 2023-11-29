<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\player\DuelPlayer;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

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

    /**
     * @param Player $player
     * @param string $matchFullName
     */
    public function addDuelPlayer(Player $player, string $matchFullName): void {
        if (isset($this->duelPlayers[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in duel players list');
        }

        $this->duelPlayers[$player->getXuid()] = new DuelPlayer($player->getXuid(), $player->getName(), $matchFullName);
    }
}