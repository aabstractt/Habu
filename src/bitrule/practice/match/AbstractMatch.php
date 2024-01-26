<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;

abstract class AbstractMatch {

    /** @var string[] */
    protected array $everyone = [];

    /**
     * @param AbstractArena $arena
     * @param int           $id
     * @param bool          $ranked
     */
    public function __construct(
        private readonly AbstractArena $arena,
        private readonly int $id,
        private readonly bool          $ranked
    ) {}

    /**
     * @return string
     */
    public function getFullName(): string {
        return $this->arena->getName() . '-' . $this->id;
    }

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
    public function getEveryone(): array {
        $everyone = [];

        foreach ($this->everyone as $xuid) {
            if (($duelProfile = ProfileManager::getInstance()->getDuelProfile($xuid)) === null) continue;

            $everyone[] = $duelProfile;
        }

        return $everyone;
    }

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