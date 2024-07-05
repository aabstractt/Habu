<?php

declare(strict_types=1);

namespace bitrule\practice\duel\events;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\impl\EventArenaProperties;
use bitrule\practice\duel\events\stage\EventStage;
use bitrule\practice\duel\events\stage\StartedEventStage;
use bitrule\practice\duel\events\stage\StartingEventStage;
use bitrule\practice\Habu;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\DuelRegistry;
use bitrule\scoreboard\ScoreboardRegistry;
use LogicException;
use pocketmine\entity\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use function array_search;
use function in_array;
use function is_int;
use function max;
use function min;

final class SumoEvent {
    use SingletonTrait;

    /**
     * Let know the server if any player has enabled the event
     *
     * @var bool $enabled
     */
    private bool $enabled = false;
    /**
     * The time given to the players to join the event
     *
     * @var int $waitingTime
     */
    private int $waitingTime = 30;
    /** @var EventStage|null $stage */
    private ?EventStage $stage = null;
    /** @var string[] $playersAlive */
    private array $playersAlive = [];
    /**
     * The arena properties of the sumo event
     * @var ArenaProperties|null $arenaProperties
     */
    private ?ArenaProperties $arenaProperties = null;
    /**
     * The cuboid of the arena
     * @var AxisAlignedBB|null $arenaCuboid
     */
    private ?AxisAlignedBB $arenaCuboid = null;
    /**
     * The cuboid of the fight area
     * @var AxisAlignedBB|null $fightCuboid
     */
    private ?AxisAlignedBB $fightCuboid = null;

    /**
     * @param EventArenaProperties|null $arenaProperties
     */
    public function loadAll(?EventArenaProperties $arenaProperties): void {
        $this->waitingTime = is_int($value = Habu::getInstance()->getConfig()->get('sumo-waiting-time')) ? $value : 30;
        $this->stage = new StartingEventStage($this->waitingTime);

        if ($arenaProperties === null) return;

        $first = [$arenaProperties->getFirstFightCorner(), $arenaProperties->getSecondFightCorner()];
        $second = [$arenaProperties->getFirstCorner(), $arenaProperties->getSecondCorner()];

        foreach ([$first, $second] as $index => [$firstCorner, $secondCorner]) {
            $cuboid = new AxisAlignedBB(
                min($firstCorner->getX(), $secondCorner->getX()),
                min($firstCorner->getY(), $secondCorner->getY()),
                min($firstCorner->getZ(), $secondCorner->getZ()),
                max($firstCorner->getX(), $secondCorner->getX()),
                max($firstCorner->getY(), $secondCorner->getY()),
                max($firstCorner->getZ(), $secondCorner->getZ())
            );

            if ($index === 1) {
                $this->arenaCuboid = $cuboid;
            } else {
                $this->fightCuboid = $cuboid;
            }
        }

        $this->arenaProperties = $arenaProperties;

        Server::getInstance()->getWorldManager()->loadWorld($arenaProperties->getOriginalName());
    }

    /**
     * @param Player $player The player that wants to join the event
     * @param bool $add If the player should be added to the players alive list
     */
    public function joinPlayer(Player $player, bool $add): void {
        if (!$this->enabled) {
            throw new LogicException('Sumo event is not enabled');
        }

        if ($this->arenaProperties === null) {
            throw new LogicException('Sumo event arena properties are not set');
        }

        $world = Server::getInstance()->getWorldManager()->getWorldByName($this->arenaProperties->getOriginalName());
        if ($world === null) {
            throw new LogicException('Sumo event world is not loaded');
        }

        $xuid = $player->getXuid();
        if (in_array($xuid, $this->playersAlive, true)) {
            throw new LogicException('Player is already in the sumo event');
        }

        $player->teleport($world->getSpawnLocation());

        Profile::resetInventory($player);
        $player->setGamemode(GameMode::ADVENTURE);

        ScoreboardRegistry::getInstance()->apply($player, $this->stage instanceof StartingEventStage ? 'event-starting' : 'event-started');

        if (!$add) return;

        $this->playersAlive[] = $xuid;

        Server::getInstance()->broadcastMessage(TextFormat::GREEN . $player->getName() . TextFormat::YELLOW . ' has joined the sumo event');
    }

    /**
     * @param Player $player
     * @param bool   $died
     */
    public function quitPlayer(Player $player, bool $died): void {
        if (!$this->enabled) return;

        $xuid = $player->getXuid();
        if (!in_array($xuid, $this->playersAlive, true)) return;

        unset($this->playersAlive[array_search($xuid, $this->playersAlive, true)]);

        if ($died) return;
        if (!$this->stage instanceof StartedEventStage || !$this->stage->isOpponent($xuid)) return;

        $this->stage->end($this, $player->getXuid());
    }

    /**
     * @param string|null $winnerXuid
     */
    public function end(?string $winnerXuid): void {
        if (!$this->enabled) return;

        $this->disable();

        $player = $winnerXuid !== null ? DuelRegistry::getInstance()->getPlayerObject($winnerXuid) : null;
        Server::getInstance()->broadcastMessage(TextFormat::GREEN . 'The sumo event has ended. ' . ($player !== null ? $player->getName() : 'Nobody') . ' has won the event');
    }

    /**
     * @param Player   $player
     * @param Location $to
     */
    public function listenPlayerMove(Player $player, Position $to): void {
        if (!$this->isPlaying($player)) return;

        if ($this->arenaCuboid === null || $this->arenaProperties === null) {
            throw new LogicException('Sumo event arena cuboid is not set');
        }

        if ($to->getWorld()->getFolderName() === $this->arenaProperties->getOriginalName()) {
            if (!$this->arenaCuboid->isVectorInside($to)) {
                $player->teleport($to->getWorld()->getSpawnLocation());

                return;
            }

            if (!$this->isFighting($player)) return;

            if ($this->fightCuboid === null) {
                throw new LogicException('Sumo event fight cuboid is not set');
            }

            if ($this->fightCuboid->isVectorInside($to)) return;
        }

        $this->quitPlayer($player, false);

        $defaultWorld = Server::getInstance()->getWorldManager()->getDefaultWorld();
        if ($defaultWorld === null) {
            throw new LogicException('Default world is not loaded');
        }

        $player->teleport($defaultWorld->getSpawnLocation());

        Profile::setDefaultAttributes($player);
        ScoreboardRegistry::getInstance()->apply($player, Habu::LOBBY_SCOREBOARD);
    }

    public function update(): void {
        if (!$this->enabled || $this->stage === null) return;

        $this->stage->update($this);
    }

    /**
     * @return bool
     */
    public function isEnabled(): bool {
        return $this->enabled;
    }

    /**
     * @param bool $enabled
     */
    public function setEnabled(bool $enabled): void {
        $this->enabled = $enabled;
    }

    /**
     * @return ArenaProperties|null
     */
    public function getArenaProperties(): ?ArenaProperties {
        return $this->arenaProperties;
    }

    /**
     * @param EventStage $stage
     */
    public function setStage(EventStage $stage): void {
        $this->stage = $stage;
    }

    /**
     * @return EventStage|null
     */
    public function getStage(): ?EventStage {
        return $this->stage;
    }

    /**
     * @param string $message
     */
    public function broadcast(string $message): void {
        foreach ($this->playersAlive as $playerXuid) {
            $player = DuelRegistry::getInstance()->getPlayerObject($playerXuid);
            if ($player === null || !$player->isOnline()) continue;

            $player->sendMessage($message);
        }
    }

    /**
     * @return string[]
     */
    public function getPlayersAlive(): array {
        return $this->playersAlive;
    }

    /**
     * @param Player $source
     *
     * @return bool
     */
    public function isPlaying(Player $source): bool {
        return $this->enabled && in_array($source->getXuid(), $this->playersAlive, true);
    }

    /**
     * @param Player $source
     *
     * @return bool
     */
    public function isFighting(Player $source): bool {
        return $this->enabled && $this->stage instanceof StartedEventStage && $this->stage->isOpponent($source->getXuid());
    }

    public function disable(): void {
        if (!$this->enabled) {
            throw new LogicException('Sumo event is not enabled');
        }

        $this->enabled = false;

        $defaultWorld = Server::getInstance()->getWorldManager()->getDefaultWorld();
        if ($defaultWorld === null) {
            throw new LogicException('Default world is not loaded');
        }

        foreach ($this->playersAlive as $xuid) {
            $player = DuelRegistry::getInstance()->getPlayerObject($xuid);
            if ($player === null || !$player->isOnline()) continue;

            Profile::resetInventory($player);
            Profile::setDefaultAttributes($player);

            ScoreboardRegistry::getInstance()->apply($player, Habu::LOBBY_SCOREBOARD);

            unset($this->playersAlive[array_search($xuid, $this->playersAlive, true)]);

            $player->teleport($defaultWorld->getSpawnLocation());
        }

        $this->stage = new StartingEventStage($this->waitingTime);
    }
}