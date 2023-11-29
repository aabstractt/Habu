<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\player\DuelPlayer;
use pocketmine\player\Player;

abstract class AbstractMatch {

    /**
     * @param int           $gridIndex
     * @param AbstractArena $arena
     * @param bool          $ranked
     */
    public function __construct(
        private readonly int           $gridIndex,
        private readonly AbstractArena $arena,
        private readonly bool          $ranked
    ) {}

    /**
     * @return string
     */
    public function getFullName(): string {
        return $this->arena->getSchematic()->getName() . '-' . $this->gridIndex;
    }

    /**
     * @return int
     */
    public function getGridIndex(): int {
        return $this->gridIndex;
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
     * @param Player[] $totalPlayers
     */
    abstract public function setup(array $totalPlayers): void;

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