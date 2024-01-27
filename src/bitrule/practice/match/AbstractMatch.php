<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\match\stage\AbstractStage;
use bitrule\practice\match\stage\PlayingStage;
use bitrule\practice\match\stage\StartingStage;
use bitrule\practice\Practice;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use RuntimeException;
use function array_filter;
use function gmdate;
use function time;

abstract class AbstractMatch {

    /**
     * The current stage of the match.
     *
     * @var AbstractStage $stage
     */
    private AbstractStage $stage;
    /** @var bool */
    private bool $loaded = false;

    /**
     * @param AbstractArena $arena
     * @param int           $id
     * @param bool          $ranked
     */
    public function __construct(
        protected readonly AbstractArena $arena,
        protected readonly int $id,
        protected readonly bool          $ranked
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
        return Server::getInstance()->getWorldManager()->getWorldByName($this->getFullName()) ?? throw new RuntimeException('World not found.');
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
    public function isLoaded(): bool {
        return $this->loaded;
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
                throw new RuntimeException('Player ' . $player->getName() . ' is not online');
            }

            ProfileManager::getInstance()->addDuelProfile($player, $this->getFullName());
        }

        if (!Server::getInstance()->getWorldManager()->loadWorld($this->getFullName())) {
            throw new RuntimeException('Failed to load world ' . $this->getFullName());
        }

        foreach ($this->getEveryone() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) {
                throw new RuntimeException('Player ' . $duelProfile->getName() . ' is not online');
            }

            $this->teleportSpawn($player);

            Practice::setProfileScoreboard($player, ProfileManager::MATCH_STARTING_SCOREBOARD);
        }

        $this->loaded = true;

        // TODO: Generate the world and teleport the players to the spawn point.
    }

    /**
     * @param Player $player
     */
    abstract public function teleportSpawn(Player $player): void;

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
        return array_filter($this->getEveryone(), fn(DuelProfile $duelProfile) => $duelProfile->isAlive());
    }

    /**
     * @return DuelProfile[]
     */
    public function getSpectators(): array {
        return array_filter($this->getEveryone(), fn(DuelProfile $duelProfile) => !$duelProfile->isAlive());
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

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        if ($identifier === 'match_duration' && $this->stage instanceof PlayingStage) {
            return gmdate('i:s', time() - $this->stage->getSeconds());
        }

        if ($identifier === 'your_ping') return (string) $player->getNetworkSession()->getPing();

        return null;
    }
}