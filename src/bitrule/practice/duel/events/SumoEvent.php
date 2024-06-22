<?php

declare(strict_types=1);

namespace bitrule\practice\duel\events;

use bitrule\practice\duel\events\stage\EventStage;
use bitrule\practice\duel\events\stage\StartingEventStage;
use bitrule\practice\duel\events\stage\StartedEventStage;
use bitrule\practice\Habu;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\DuelRegistry;
use LogicException;
use pocketmine\entity\Location;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;

final class SumoEvent {
    use SingletonTrait;

    /**
     * Let know the server if any player has enabled the event
     *
     * @var bool $enabled
     */
    private bool $enabled = false;

    private string $worldName = 'Sumo';
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
     * This is the spawn for the first player
     * @var Location|null $firstSpawn
     */
    private ?Location $firstSpawn = null;
    /**
     * This is the spawn for the second player
     * @var Location|null $secondSpawn
     */
    private ?Location $secondSpawn = null;
    /**
     * This is the spawn for players non fighting
     * @var Location|null $thirdSpawn
     */
    private ?Location $thirdSpawn = null;

    /**
     * @param Config $config
     */
    public function loadAll(Config $config): void {
        $this->worldName = is_string($value = $config->get('sumo-world-name')) ? $value : 'Sumo';
        if (!Server::getInstance()->getWorldManager()->isWorldGenerated($this->worldName)) {
            throw new LogicException('Sumo world is not generated');
        }

        Server::getInstance()->getWorldManager()->loadWorld($this->worldName);

        $this->waitingTime = is_int($value = $config->get('sumo-waiting-time')) ? $value : 30;

        $this->stage = new StartingEventStage($this->waitingTime);
    }

    /**
     * @param Player $player The player that wants to join the event
     * @param bool $add If the player should be added to the players alive list
     */
    public function joinPlayer(Player $player, bool $add): void {
        if (!$this->enabled) {
            throw new LogicException('Sumo event is not enabled');
        }

        if ($this->thirdSpawn == null) {
            throw new LogicException('Sumo event third spawn is not set');
        }

        $xuid = $player->getXuid();
        if (in_array($xuid, $this->playersAlive, true)) {
            throw new LogicException('Player is already in the sumo event');
        }

        if ($add) $this->playersAlive[] = $xuid;

        $player->teleport($this->thirdSpawn);

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

    public function end(): void {}

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
     * @return string
     */
    public function getWorldName(): string {
        return $this->worldName ?? throw new LogicException('Sumo event world name is not set');
    }

    /**
     * @return Location|null
     */
    public function getFirstSpawn(): ?Location {
        return $this->firstSpawn;
    }

    /**
     * @return Location|null
     */
    public function getSecondSpawn(): ?Location {
        return $this->secondSpawn;
    }

    /**
     * @param EventStage $stage
     */
    public function setStage(EventStage $stage): void {
        $this->stage = $stage;
    }

    /**
     * @return EventStage
     */
    public function getStage(): EventStage {
        return $this->stage ?? throw new LogicException('Sumo event stage is not set');
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

    public function disable(): void {
        if (!$this->enabled) {
            throw new LogicException('Sumo event is not enabled');
        }

        $this->enabled = false;

        $this->stage = new StartingEventStage($this->waitingTime);
    }
}