<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\player\DuelPlayer;
use bitrule\practice\player\LocalPlayer;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class PlayerManager {
    use SingletonTrait;

    /** @var array<string, LocalPlayer> */
    private array $localPlayers = [];

    /** @var array<string, DuelPlayer> */
    private array $duelPlayers = [];

    /**
     * @param string $xuid
     *
     * @return LocalPlayer|null
     */
    public function getLocalPlayer(string $xuid): ?LocalPlayer {
        return $this->localPlayers[$xuid] ?? null;
    }

    /**
     * @param Player $player
     */
    public function addLocalPlayer(Player $player): void {
        if (isset($this->localPlayers[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in local players list');
        }

        $this->localPlayers[$player->getXuid()] = new LocalPlayer($player->getXuid(), $player->getName());
    }

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