<?php

declare(strict_types=1);

namespace bitrule\practice\duel;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\duel\stage\AbstractStage;
use bitrule\practice\duel\stage\EndingStage;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\duel\stage\StartingStage;
use bitrule\practice\kit\Kit;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\Practice;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\profile\LocalProfile;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;

abstract class Duel {

    public const FIRST_SPAWN_ID = 0;
    public const SECOND_SPAWN_ID = 1;
    public const SPECTATOR_SPAWN_ID = 2;

    /**
     * The current stage of the match.
     *
     * @var AbstractStage $stage
     */
    protected AbstractStage $stage;
    /** @var bool */
    protected bool $loaded = false;

    /** @var array<string, DuelProfile> */
    protected array $players = [];
    /** @var array<string, int> */
    protected array $playersSpawn = [];

    /**
     * @param AbstractArena    $arena
     * @param Kit              $kit
     * @param int              $id
     * @param bool             $ranked
     */
    public function __construct(
        protected readonly AbstractArena $arena,
        protected readonly Kit $kit,
        protected readonly int $id,
        protected readonly bool $ranked
    ) {
        $this->stage = new StartingStage();
    }

    /**
     * This method is used to add the player to the cache
     * For example:
     * - Single Match: add the player to the players array.
     * - Team Match: add the player to the spectators team.
     *
     * @param Player $player
     */
    abstract public function joinSpectator(Player $player): void;

    /**
     * Called after the player was added to the Match
     * This is used to teleport the player to the spawn point.
     *
     * @param Player $player
     */
    protected function postJoinSpectator(Player $player): void {
        if (!$this->isLoaded()) {
            throw new RuntimeException('Match not loaded.');
        }

        $this->players[$player->getXuid()] = DuelProfile::create(
            $player,
            $this->getFullName(),
            false
        );

        $this->teleportSpawn($player);
    }

    /**
     * @param Player $player
     */
    public function teleportSpawn(Player $player): void {
        if (($spawnId = $this->getSpawnId($player->getXuid())) === -1) {
            throw new RuntimeException('Player not found in match.');
        }

        $player->teleport(Position::fromObject(
            match ($spawnId) {
                0 => $this->arena->getFirstPosition(),
                1 => $this->arena->getSecondPosition(),
                default => $this->getWorld()->getSpawnLocation()
            },
            $this->getWorld()
        ));
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function prepare(array $totalPlayers): void {
        if (!Server::getInstance()->getWorldManager()->loadWorld($this->getFullName())) {
            throw new RuntimeException('Failed to load world ' . $this->getFullName());
        }

        foreach ($totalPlayers as $player) {
            if (!$player->isOnline()) {
                throw new RuntimeException('Player ' . $player->getName() . ' is not online');
            }

            $this->players[$player->getXuid()] = DuelProfile::create(
                $player,
                $this->getFullName(),
                true
            );
        }

        foreach ($this->getEveryone() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) {
                throw new RuntimeException('Player ' . $duelProfile->getName() . ' is not online');
            }

            LocalProfile::setDefaultAttributes($player);

            $this->processPlayerPrepare($player, $duelProfile);
            $this->teleportSpawn($player);

            Practice::setProfileScoreboard($player, ProfileRegistry::MATCH_STARTING_SCOREBOARD);
        }

        $this->loaded = true;
    }

    /**
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    abstract public function processPlayerPrepare(Player $player, DuelProfile $duelProfile): void;

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
        return $this->playersSpawn[$xuid] ?? throw new RuntimeException('Player not found in match.');
    }

    /**
     * This method is called when the match stage change to Ending.
     * Usually is used to send the match results to the players.
     */
    abstract public function end(): void;

    /**
     * This method is called when the countdown ends.
     * Usually is used to delete the world
     * and teleport the players to the spawn point.
     */
    public function postEnd(): void {
        $spectators = $this->getSpectators();
        $players = $this->getPlayers();
        $winner = $this->getWinner();

        foreach ($this->getEveryone() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) {
                throw new RuntimeException('Player ' . $duelProfile->getName() . ' is not online');
            }

            $this->removePlayer($player, false);
            $this->postRemovePlayer($player);

            $this->processPlayerEnd($player, $duelProfile);

//            $localProfile = ProfileRegistry::getInstance()->getLocalProfile($player->getXuid());
//            if ($localProfile === null) {
//                throw new RuntimeException('Local profile not found for player: ' . $player->getName());
//            }
//
//            $localProfile->joinLobby($player, true);
        }

        $this->loaded = false;

//        (new MatchEndEvent($this, $winner, $players, $spectators, $this->someoneDisconnected))->call();
    }

    /**
     * Process the player when the match ends.
     *
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    abstract public function processPlayerEnd(Player $player, DuelProfile $duelProfile): void;

    /**
     * Remove a player from the match.
     * Check if the match can end.
     * Usually is checked when the player died or left the match.
     *
     * @param Player $player
     * @param bool   $canEnd
     */
    abstract public function removePlayer(Player $player, bool $canEnd): void;

    /**
     * Remove the player from the cache.
     *
     * @param Player $player
     */
    public function postRemovePlayer(Player $player): void {
        $duelProfile = $this->players[$player->getXuid()] ?? null;
        if ($duelProfile === null) return;

        unset($this->players[$player->getXuid()], $this->playersSpawn[$player->getXuid()]);

        DuelRegistry::getInstance()->quitPlayer($player->getXuid());
    }

    /**
     * @return DuelProfile[]
     */
    public function getEveryone(): array {
        return $this->players;
    }

    /**
     * @return DuelProfile[]
     */
    public function getPlayers(): array {
        return array_filter(
            $this->players,
            fn(DuelProfile $duelProfile) => $duelProfile->isPlaying()
        );
    }

    /**
     * @return DuelProfile[]
     */
    public function getAlive(): array {
        return array_filter(
            $this->players,
            fn(DuelProfile $duelProfile) => $duelProfile->isAlive()
        );
    }

    /**
     *
     * @return DuelProfile[]
     */
    public function getSpectators(): array {
        return array_filter(
            $this->players,
            fn(DuelProfile $duelProfile) => !$duelProfile->isAlive()
        );
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
     * @return Kit
     */
    public function getKit(): Kit {
        return $this->kit;
    }

    /**
     * @return \bitrule\practice\duel\stage\AbstractStage
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
     * @return DuelProfile|null
     */
    public function getWinner(): ?DuelProfile {
        return $this->getAlive()[0] ?? null;
    }

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        if ($identifier === 'match_duration' && ($this->stage instanceof PlayingStage || $this->stage instanceof EndingStage)) {
            return gmdate('i:s', $this->stage instanceof PlayingStage ? $this->stage->getSeconds() : $this->stage->getDuration());
        }

        if ($identifier === 'your_ping') return (string) $player->getNetworkSession()->getPing();

        if ($this->stage instanceof EndingStage) {
            if ($identifier === 'match_ending_defeat' && $player->isSpectator()) return '';
            if ($identifier === 'match_ending_victory' && $player->isSurvival()) return '';
        }

        return null;
    }
}