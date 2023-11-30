<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\profile\DuelProfile;
use bitrule\practice\profile\LocalProfile;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class ProfileManager {
    use SingletonTrait;

    /** @var array<string, LocalProfile> */
    private array $localProfiles = [];

    /** @var array<string, DuelProfile> */
    private array $duelProfiles = [];

    /**
     * @param string $xuid
     *
     * @return LocalProfile|null
     */
    public function getLocalProfile(string $xuid): ?LocalProfile {
        return $this->localProfiles[$xuid] ?? null;
    }

    /**
     * @param Player $player
     */
    public function addLocalProfile(Player $player): void {
        if (isset($this->localProfiles[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in local players list');
        }

        $this->localProfiles[$player->getXuid()] = new LocalProfile($player->getXuid(), $player->getName());
    }

    /**
     * @param string $xuid
     *
     * @return DuelProfile|null
     */
    public function getDuelProfile(string $xuid): ?DuelProfile {
        return $this->duelProfiles[$xuid] ?? null;
    }

    /**
     * @param Player $player
     * @param string $matchFullName
     */
    public function addDuelProfile(Player $player, string $matchFullName): void {
        if (isset($this->duelProfiles[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in duel players list');
        }

        $this->duelProfiles[$player->getXuid()] = new DuelProfile($player->getXuid(), $player->getName(), $matchFullName);
    }

    /**
     * @param string $xuid
     */
    public function removeProfile(string $xuid): void {
        unset($this->localProfiles[$xuid], $this->duelProfiles[$xuid]);
    }
}