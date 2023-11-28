<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\player\DuelPlayer;
use pocketmine\player\Player;

abstract class AbstractMatch {

    /**
     * @param int           $id
     * @param AbstractArena $arena
     * @param bool          $ranked
     */
    public function __construct(
        private readonly int           $id,
        private readonly AbstractArena $arena,
        private readonly bool          $ranked
    ) {}

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return AbstractArena
     */
    public function getArena(): AbstractArena {
        return $this->arena;
    }

    /**
     * @return bool
     */
    public function isRanked(): bool {
        return $this->ranked;
    }

    /**
     * @param Player $player
     */
    abstract public function removePlayer(Player $player): void;

    /**
     * @return DuelPlayer[]
     */
    abstract public function getEveryone(): array;

    /**
     * @return DuelPlayer[]
     */
    public function getSpectators(): array {
        $spectators = [];

        foreach ($this->getEveryone() as $duelPlayer) {
            if ($duelPlayer->isAlive()) continue;

            $spectators[] = $duelPlayer;
        }

        return $spectators;
    }

    public function broadcastMessage(string $message): void {
        foreach ($this->getEveryone() as $duelPlayer) {
            $duelPlayer->sendMessage($message);
        }
    }
}