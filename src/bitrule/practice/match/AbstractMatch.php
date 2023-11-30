<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\profile\DuelProfile;
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
        return $this->arena->getName() . '-' . $this->gridIndex;
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
     * @return DuelProfile[]
     */
    abstract public function getEveryone(): array;

    /**
     * @return DuelProfile[]
     */
    public function getSpectators(): array {
        $spectators = [];

        foreach ($this->getEveryone() as $duelProfile) {
            if ($duelProfile->isAlive()) continue;

            $spectators[] = $duelProfile;
        }

        return $spectators;
    }

    /**
     * @param string $message
     * @param bool   $includeSpectators
     */
    public function broadcastMessage(string $message, bool $includeSpectators = true): void {
        foreach ($this->getEveryone() as $duelProfile) {
            if (!$duelProfile->isAlive() && !$includeSpectators) continue;

            $duelProfile->sendMessage($message);
        }
    }
}