<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\match\stage\AbstractStage;
use bitrule\practice\match\stage\StartingStage;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;

abstract class AbstractMatch {

    /**
     * The current stage of the match.
     *
     * @var AbstractStage $stage
     */
    private AbstractStage $stage;

    /**
     * @param AbstractArena $arena
     * @param int           $id
     * @param bool          $ranked
     */
    public function __construct(
        private readonly AbstractArena $arena,
        private readonly int $id,
        private readonly bool          $ranked
    ) {
        $this->stage = new StartingStage();
    }

    /**
     * @return string
     */
    public function getFullName(): string {
        return $this->arena->getName() . '-' . $this->id;
    }

    /**
     * Gets the match's world.
     * @return World
     */
    public function getWorld(): World {
        return Server::getInstance()->getWorldManager()->getWorldByName($this->getFullName()) ?? throw new \RuntimeException('World not found.');
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
     * @return AbstractStage
     */
    public function getStage(): AbstractStage {
        return $this->stage;
    }

    /**
     * @param AbstractStage $stage
     */
    public function setStage(AbstractStage $stage): void {
        $this->stage = $stage;
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
     * @param Player[] $totalPlayers
     */
    public function postSetup(array $totalPlayers): void {
        foreach ($totalPlayers as $player) {
            if (!$player->isOnline()) {
                throw new \RuntimeException('Player ' . $player->getName() . ' is not online');
            }

            ProfileManager::getInstance()->addDuelProfile($player, $this->getFullName());
        }
    }

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
    public function getAlive(): array {
        $alive = [];

        foreach ($this->getEveryone() as $duelProfile) {
            if (!$duelProfile->isAlive()) continue;

            $alive[] = $duelProfile;
        }

        return $alive;
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