<?php

declare(strict_types=1);

namespace bitrule\practice\match\impl;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\player\DuelPlayer;
use pocketmine\player\Player;

final class SingleMatchImpl extends AbstractMatch {

    /**
     * @param Player $player
     */
    public function removePlayer(Player $player): void {
        // TODO: Implement removePlayer() method.
    }

    /**
     * @return DuelPlayer[]
     */
    public function getEveryone(): array {
        // TODO: Implement getEveryone() method.
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function setup(array $totalPlayers): void {
        // TODO: Implement setup() method.
    }
}