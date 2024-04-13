<?php

namespace bitrule\practice\duel\impl\trait;

use bitrule\practice\duel\Duel;
use RuntimeException;

trait NormalDuelSpawnTrait {
    /** @var array<string, int> */
    protected array $playersSpawn = [];

    /**
     * Get the spawn id of the player
     * If is single match the spawn id is the index of the player in the players array.
     * If is team match the spawn id is the team id of the player.
     *
     * @param string $xuid
     *
     * @return int
     */
    public function getSpawnId(string $xuid): int {
        if (!$this instanceof Duel) {
            throw new RuntimeException('This trait can only be used in Duel class.');
        }

        return $this->playersSpawn[$xuid] ?? -1;
    }
}