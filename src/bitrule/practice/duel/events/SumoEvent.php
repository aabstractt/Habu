<?php

declare(strict_types=1);

namespace bitrule\practice\duel\events;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\impl\EventArenaProperties;
use bitrule\practice\duel\events\stage\EventStage;
use bitrule\practice\duel\events\stage\StartingEventStage;
use bitrule\practice\duel\events\stage\StartedEventStage;
use bitrule\practice\Habu;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\DuelRegistry;
use LogicException;
use pocketmine\math\AxisAlignedBB;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

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
    /**
     * @var EventStage|null $stage
     */
    private ?EventStage $stage = null;
    /**
     * @var string[] $playersAlive
     */
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
     * @param Config                    $config
     */
    public function loadAll(?EventArenaProperties $arenaProperties, Config $config): void {
        $this->waitingTime = is_int($value = $config->get('waiting-time')) ? $value : 30;
        $this->stage = new StartingEventStage($this->waitingTime);

        if ($arenaProperties === null) return;

        $first = [$arenaProperties->getFirstFightCorner(), $arenaProperties->getSecondFightCorner()];
        $second = [$arenaProperties->getFirstFightCorner(), $arenaProperties->getSecondFightCorner()];

        foreach ([$first, $second] as $index => [$firstCorner, $secondCorner]) {
            $cuboid = new AxisAlignedBB(
                min($firstCorner->getX(), $secondCorner->getX()),
                min($firstCorner->getY(), $secondCorner->getY()),
                min($firstCorner->getZ(), $secondCorner->getZ()),
                max($firstCorner->getX(), $secondCorner->getX()),
                max($firstCorner->getY(), $secondCorner->getY()),
                max($firstCorner->getZ(), $secondCorner->getZ())
            );

            if ($index === 0) {
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

        if ($add) $this->playersAlive[] = $xuid;

        $player->teleport($world->getSpawnLocation());

        Profile::resetInventory($player);
        $player->setGamemode(GameMode::ADVENTURE);
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
        if (!$this->stage instanceof StartedEventStage) return;
        if (!$this->stage->isOpponent($xuid)) return;

        $this->stage->end($this, $player->getXuid());
    }

    /**
     * @param string|null $winnerXuid
     */
    public function end(?string $winnerXuid): void {
        $this->disable();

        $player = $winnerXuid !== null ? DuelRegistry::getInstance()->getPlayerObject($winnerXuid) : null;
        Server::getInstance()->broadcastMessage(TextFormat::GREEN . 'The sumo event has ended. ' . ($player !== null ? $player->getName() : 'Nobody') . ' has won the event');
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
     * @param Position $to
     * @param bool     $fight
     *
     * @return bool
     */
    public function isVectorInside(Position $to, bool $fight): bool {
        if ($this->arenaCuboid === null || $this->fightCuboid === null || $this->arenaProperties === null) return false;
        if ($to->getWorld()->getFolderName() !== $this->arenaProperties->getOriginalName()) return false;

        return ($fight ? $this->fightCuboid : $this->arenaCuboid)->isVectorInside($to);
    }

    /**
     * @param Player $source
     *
     * @return bool
     */
    public function isPlaying(Player $source): bool {
        return $this->enabled && in_array($source->getXuid(), $this->playersAlive, true);
    }

    public function disable(): void {
        if (!$this->enabled) {
            throw new LogicException('Sumo event is not enabled');
        }

        $this->enabled = false;

        $this->stage = new StartingEventStage($this->waitingTime);
    }
}