<?php

declare(strict_types=1);

namespace bitrule\practice\match\impl;

use bitrule\practice\manager\ProfileManager;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;

final class SingleMatchImpl extends AbstractMatch {

    /** @var string[] */
    private array $players = [];

    /**
     * @param Player $player
     */
    public function removePlayer(Player $player): void {
        $this->players = array_diff($this->players, [$player->getXuid()]);
    }

    /**
     * @return DuelProfile[]
     */
    public function getEveryone(): array {
        /** @var DuelProfile[] $everyone */
        $everyone = [];

        foreach ($this->players as $xuid) {
            if (($duelProfile = ProfileManager::getInstance()->getDuelProfile($xuid)) === null) continue;

            $everyone[] = $duelProfile;
        }

        return $everyone;
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function setup(array $totalPlayers): void {
        $this->players = array_map(
            fn(Player $player) => $player->getXuid(),
            $totalPlayers
        );
    }
}