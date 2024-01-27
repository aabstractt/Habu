<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\kit\Kit;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\match\stage\AbstractStage;
use bitrule\practice\match\stage\EndingStage;
use bitrule\practice\match\stage\PlayingStage;
use bitrule\practice\match\stage\StartingStage;
use bitrule\practice\Practice;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\TranslationKeys;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\world\World;
use RuntimeException;
use function array_filter;
use function gmdate;

abstract class AbstractMatch {

    /**
     * The current stage of the match.
     *
     * @var AbstractStage $stage
     */
    protected AbstractStage $stage;
    /** @var bool */
    protected bool $loaded = false;

    /**
     * @param AbstractArena $arena
     * @param Kit           $kit
     * @param int           $id
     * @param bool          $ranked
     */
    public function __construct(
        protected readonly AbstractArena $arena,
        protected readonly Kit $kit,
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
     * @param Player $player
     */
    abstract public function joinSpectator(Player $player): void;

    /**
     * @param Player[] $totalPlayers
     */
    abstract public function prepare(array $totalPlayers): void;

    /**
     * @param Player[] $totalPlayers
     */
    public function postPrepare(array $totalPlayers): void {
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

            $player->sendMessage(TranslationKeys::MATCH_OPPONENT_FOUND->build(
                $this->getOpponentName($duelProfile->getXuid()) ?? 'None',
                $this->isRanked() ? 'Ranked' : 'Unranked',
                $this->kit->getName()
            ));

            $this->teleportSpawn($player);

            Practice::setProfileScoreboard($player, ProfileManager::MATCH_STARTING_SCOREBOARD);
        }

        $this->loaded = true;
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
        foreach ($this->getEveryone() as $duelProfile) {
            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) {
                throw new RuntimeException('Player ' . $duelProfile->getName() . ' is not online');
            }

            $this->removePlayer($player, false);

            $localProfile = ProfileManager::getInstance()->getLocalProfile($player->getXuid());
            if ($localProfile === null) {
                throw new RuntimeException('Local profile not found for player: ' . $player->getName());
            }

            $localProfile->joinLobby($player);
        }

        $this->loaded = false;
    }

    /**
     * @param Player $player
     */
    abstract public function teleportSpawn(Player $player): void;

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
     * @param string $xuid
     *
     * @return string|null
     */
    abstract public function getOpponentName(string $xuid): ?string;

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