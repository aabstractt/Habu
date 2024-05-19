<?php

declare(strict_types=1);

namespace bitrule\practice\duel;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\duel\stage\AbstractStage;
use bitrule\practice\duel\stage\EndingStage;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\duel\stage\StartingStage;
use bitrule\practice\kit\Kit;
use bitrule\practice\Habu;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\Position;
use pocketmine\world\World;
use RuntimeException;
use function array_filter;
use function array_key_first;
use function count;
use function gmdate;

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
    /** @var bool */
    protected bool $ending = false;

    /** @var array<string, DuelMember> */
    protected array $members = [];
    /** @var array<string, int> */
    protected array $playersSpawn = [];

    /**
     * @param ArenaProperties $arenaProperties
     * @param Kit             $kit
     * @param int             $id
     * @param bool            $ranked
     */
    public function __construct(
        protected readonly ArenaProperties $arenaProperties,
        protected readonly Kit             $kit,
        protected readonly int             $id,
        protected readonly bool            $ranked
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

        $this->members[$player->getXuid()] = DuelMember::spectator($player);

        $this->teleportSpawn($player);
    }

    /**
     * @param Player $player
     */
    public function teleportSpawn(Player $player): void {
        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) {
            throw new RuntimeException('Player not found in match.');
        }

        $player->teleport(Position::fromObject(
            match ($spawnId) {
                self::FIRST_SPAWN_ID => $this->arenaProperties->getFirstPosition(),
                self::SECOND_SPAWN_ID => $this->arenaProperties->getSecondPosition(),
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

            $profile = ProfileRegistry::getInstance()->getProfile($player->getXuid());
            if ($profile === null) {
                throw new RuntimeException('Local profile not found for player: ' . $player->getName());
            }

            $this->members[$player->getXuid()] = DuelMember::normal($player, $profile->getElo());

            Profile::setDefaultAttributes($player);

            $profile->setKnockbackProfile($this->kit->getKnockbackProfile());
        }

        foreach ($this->members as $duelMember) {
            $player = $duelMember->toPlayer();
            if ($player === null || !$player->isOnline()) {
                throw new RuntimeException('Player ' . $duelMember->getName() . ' is not online');
            }

            $this->processPlayerPrepare($player, $duelMember);
            $this->teleportSpawn($player);

            $this->kit->applyOn($player);

            Habu::setProfileScoreboard($player, ProfileRegistry::MATCH_STARTING_SCOREBOARD);
        }

        $this->loaded = true;
    }

    /**
     * @param Player     $player
     * @param DuelMember $duelMember
     */
    abstract public function processPlayerPrepare(Player $player, DuelMember $duelMember): void;

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
        return $this->playersSpawn[$xuid] ?? -1;
    }

    /**
     * Called when the duel stage changes
     * to Ending.
     */
    public function end(): void {
        if ($this->ending) return;

        $this->ending = true;

        $this->stage = EndingStage::create($this->stage instanceof PlayingStage ? $this->stage->getSeconds() : 0);

        foreach ($this->getEveryone() as $duelMember) {
            $player = $duelMember->toPlayer();
            if ($player === null) continue;

            $this->processPlayerEnd($player, $duelMember);
        }
    }

    /**
     * This method is called when the countdown ends.
     * Usually is used to delete the world
     * and teleport the players to the spawn point.
     */
    public function postEnd(): void {
        foreach ($this->getEveryone() as $duelMember) {
            $player = $duelMember->toPlayer();
            if ($player === null || !$player->isOnline()) {
                throw new RuntimeException('Player ' . $duelMember->getName() . ' is not online');
            }

            $this->removePlayer($player, false);
            $this->postRemovePlayer($player);
        }

        $this->loaded = false;

        DuelRegistry::getInstance()->endDuel($this);
    }

    /**
     * Process the player when the match ends.
     *
     * @param Player     $player
     * @param DuelMember $duelMember
     */
    public function processPlayerEnd(Player $player, DuelMember $duelMember): void {
        Habu::setProfileScoreboard($player, ProfileRegistry::MATCH_ENDING_SCOREBOARD);
    }

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
        $duelMember = $this->members[$player->getXuid()] ?? null;
        if ($duelMember === null) return;

        unset($this->members[$player->getXuid()]);

        DuelRegistry::getInstance()->quitPlayer($player->getXuid());

        $profile = ProfileRegistry::getInstance()->getProfile($player->getXuid());
        if ($profile === null) {
            throw new RuntimeException('Local profile not found for player: ' . $player->getName());
        }

        $profile->joinLobby($player, true);
    }

    /**
     * @return DuelMember[]
     */
    public function getEveryone(): array {
        return $this->members;
    }

    /**
     * @return DuelMember[]
     */
    public function getMembers(): array {
        return array_filter(
            $this->members,
            fn(DuelMember $duelMember) => $duelMember->isPlaying()
        );
    }

    /**
     * @return DuelMember[]
     */
    public function getAlive(): array {
        return array_filter(
            $this->members,
            fn(DuelMember $duelMember) => $duelMember->isAlive()
        );
    }

    /**
     * @return DuelMember[]
     */
    public function getSpectators(): array {
        return array_filter(
            $this->members,
            fn(DuelMember $duelMember) => !$duelMember->isAlive()
        );
    }

    /**
     * @param string $xuid
     *
     * @return DuelMember|null
     */
    public function getMember(string $xuid): ?DuelMember {
        return $this->members[$xuid] ?? null;
    }

    /**
     * @param string $message
     * @param bool   $includeSpectators
     */
    public function broadcastMessage(string $message, bool $includeSpectators = true): void {
        foreach ($this->getEveryone() as $duelMember) {
            if (!$duelMember->isAlive() && !$includeSpectators) continue;

            $duelMember->sendMessage($message);
        }
    }

    /**
     * @return bool
     */
    public function hasSomeoneDisconnected(): bool {
        return count(array_filter(
            $this->getMembers(),
            fn(DuelMember $duelMember): bool => ($player = $duelMember->toPlayer()) === null || !$player->isOnline()
            )) > 0;
    }

    /**
     * @return string
     */
    public function getFullName(): string {
        return $this->arenaProperties->getOriginalName() . '-' . $this->id;
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
     * @return ArenaProperties
     */
    public function getArenaProperties(): ArenaProperties {
        return $this->arenaProperties;
    }

    /**
     * @return Kit
     */
    public function getKit(): Kit {
        return $this->kit;
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
     * @return DuelMember|null
     */
    public function getWinner(): ?DuelMember {
        if (count($this->getAlive()) !== 1) return null;

        $firstKey = array_key_first($this->getAlive());
        if ($firstKey === null) return null;

        return $this->members[$firstKey] ?? null;
    }

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        if ($identifier === 'duel-duration' && ($this->stage instanceof PlayingStage || $this->stage instanceof EndingStage)) {
            return gmdate('i:s', $this->stage instanceof PlayingStage ? $this->stage->getSeconds() : $this->stage->getDuration());
        }

        if ($identifier === 'your-ping') return (string) $player->getNetworkSession()->getPing();

        $duelPlayer = $this->getMember($player->getXuid());
        if ($duelPlayer === null) return null;

        if ($this->stage instanceof EndingStage && $duelPlayer->isPlaying()) {
            if ($identifier === 'duel-ending-defeat' && !$duelPlayer->isAlive()) return '';
            if ($identifier === 'duel-ending-victory' && $duelPlayer->isAlive()) return '';
        }

        return null;
    }
}