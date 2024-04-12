<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use pocketmine\player\Player;
use RuntimeException;

trait SpectatingDuelTrait {

    /**
     * This method is called when a player joins the match.
     * Add the player to the match and teleport them to their spawn.
     *
     * @param Player $player
     */
    public function joinSpectator(Player $player): void {
        if (!$this instanceof Duel) {
            throw new RuntimeException('This trait can only be used in Duel class.');
        }

        if (!$this->isLoaded()) {
            throw new RuntimeException('Match not loaded.');
        }

        $this->playersSpawn[$player->getXuid()] = Duel::SPECTATOR_SPAWN_ID;

        $this->postJoinSpectator($player);
    }
}