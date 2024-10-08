<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\impl\BedFightArenaProperties;
use bitrule\practice\arena\impl\BridgeArenaProperties;
use bitrule\practice\arena\impl\EventArenaProperties;
use bitrule\practice\Habu;
use InvalidArgumentException;
use pocketmine\entity\Location;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function strtolower;

abstract class AbstractArenaSetup {

    /** @var string|null */
    private ?string $name = null;

    /** @var Location|null */
    private ?Location $firstPosition = null;
    /** @var Location|null */
    private ?Location $secondPosition = null;

    /**
     * The first corner of the arena.
     * This is used to make the arena cuboid.
     *
     * @var Vector3|null $firstCorner
     */
    private ?Vector3 $firstCorner = null;
    /**
     * The second corner of the arena.
     * This is used to make the arena cuboid.
     *
     * @var Vector3|null $secondCorner
     */
    private ?Vector3 $secondCorner = null;

    /** @var string|null */
    private ?string $primaryKit = null;

    /**
     * Whether the setup has started.
     * @var bool
     */
    private bool $started = false;

    /**
     * The step spawn point to set.
     * @var int
     */
    protected int $spawnStep = 0;

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name ?? throw new RuntimeException('Arena name is not set');
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void {
        $this->name = $name;
    }

    /**
     * @return Location|null
     */
    public function getFirstPosition(): ?Location {
        return $this->firstPosition;
    }

    /**
     * @param Location|null $firstPosition
     */
    public function setFirstPosition(?Location $firstPosition): void {
        $this->firstPosition = $firstPosition;
    }

    /**
     * @return Location|null
     */
    public function getSecondPosition(): ?Location {
        return $this->secondPosition;
    }

    /**
     * @param Location|null $secondPosition
     */
    public function setSecondPosition(?Location $secondPosition): void {
        $this->secondPosition = $secondPosition;
    }

    /**
     * @return Vector3|null
     */
    public function getFirstCorner(): ?Vector3 {
        return $this->firstCorner;
    }

    /**
     * @param Vector3|null $firstCorner
     */
    public function setFirstCorner(?Vector3 $firstCorner): void {
        $this->firstCorner = $firstCorner;
    }

    /**
     * @return Vector3|null
     */
    public function getSecondCorner(): ?Vector3 {
        return $this->secondCorner;
    }

    /**
     * @param Vector3|null $secondCorner
     */
    public function setSecondCorner(?Vector3 $secondCorner): void {
        $this->secondCorner = $secondCorner;
    }

    /**
     * @return string|null
     */
    public function getPrimaryKit(): ?string {
        return $this->primaryKit;
    }

    /**
     * @param string|null $primaryKit
     */
    public function setPrimaryKit(?string $primaryKit): void {
        $this->primaryKit = $primaryKit;
    }

    /**
     * @param int      $step
     * @param Location $position
     */
    public function setPositionByStep(int $step, Location $position): void {
        if ($step === 0) {
            $this->setFirstPosition($position);
        } else {
            $this->setSecondPosition($position);
        }
    }

    /**
     * @return bool
     */
    public function isStarted(): bool {
        return $this->started;
    }

    /**
     * @return int
     */
    public function getSpawnStep(): int {
        return $this->spawnStep;
    }

    /**
     * Increases the spawn step by 1.
     */
    public function increaseSpawnStep(): void {
        $this->spawnStep++;
    }

    /**
     * Decreases the spawn step by 1.
     */
    public function decreaseSpawnStep(): void {
        $this->spawnStep--;

        if ($this->spawnStep < 0) {
            $this->spawnStep = 0;
        }
    }

    /**
     * Returns the type of arena setup.
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * This method is called when the setup is started.
     * This is where you should set the player's inventory, gamemode, etc.
     *
     * @param Player $player
     */
    public function setup(Player $player): void {
        if ($this->name === null) {
            throw new RuntimeException('Arena name is not set');
        }

        $worldManager = Server::getInstance()->getWorldManager();
        if (!$worldManager->isWorldGenerated($this->name)) {
            Habu::getInstance()->getLogger()->info('Generating world ' . $this->name);
        }

        if (!$worldManager->loadWorld($this->name)) {
            throw new RuntimeException('Failed to load world ' . $this->name);
        }

        $world = $worldManager->getWorldByName($this->name);
        if ($world === null) {
            throw new RuntimeException('World ' . $this->name . ' does not exist');
        }

        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();

        $player->getInventory()->setItem(0, VanillaItems::STICK()
            ->setCustomName(TextFormat::RESET . TextFormat::YELLOW . 'Select Spawns')
            ->setNamedTag(CompoundTag::create()->setString('arena', $this->name))
        );

        $player->setGamemode(GameMode::CREATIVE);
        $player->setFlying(true);

        $player->teleport($world->getSpawnLocation());

        $player->sendMessage(TextFormat::GREEN . 'Setup started. Use the stick to select the spawns.');
        $player->sendMessage(TextFormat::YELLOW . 'Shift + Break Block to increase the spawn step');
        $player->sendMessage(TextFormat::YELLOW . 'Break Block to decrease the spawn step');
        $player->sendMessage(TextFormat::YELLOW . 'Break block with stick to set the spawn step');

        $this->started = true;
    }

    /**
     * Loads the arena properties.
     *
     * @param ArenaProperties $properties
     */
    public function load(ArenaProperties $properties): void {
        $this->name = $properties->getOriginalName();

        $this->firstPosition = $properties->getFirstPosition();
        $this->secondPosition = $properties->getSecondPosition();

        $this->firstCorner = $properties->getFirstCorner();
        $this->secondCorner = $properties->getSecondCorner();

        $this->primaryKit = $properties->getPrimaryKit();
    }

    /**
     * This method is called when the arena is created into the arena manager.
     * This is where you should set the arena's properties.
     *
     * @return array
     */
    public function getProperties(): array {
        if (!$this->started) {
            throw new RuntimeException('Setup has not started');
        }

        if ($this->name === null) {
            throw new RuntimeException('Arena name is not set');
        }

        if ($this->firstPosition === null) {
            throw new RuntimeException('First position is not set');
        }

        if ($this->secondPosition === null) {
            throw new RuntimeException('Second position is not set');
        }

        if ($this->firstCorner === null) {
            throw new RuntimeException('First corner is not set');
        }

        if ($this->secondCorner === null) {
            throw new RuntimeException('Second corner is not set');
        }

        if ($this->primaryKit === null) {
            throw new RuntimeException('Primary kit is not set');
        }

        return [
        	'first-position' => $this->firstPosition,
        	'second-position' => $this->secondPosition,
        	'first-corner' => $this->firstCorner,
        	'second-corner' => $this->secondCorner,
        	'primary-kit' => $this->primaryKit,
        	'type' => $this->getType()
        ];
    }

    /**
     * @param string $type
     *
     * @return AbstractArenaSetup
     */
    public static function from(string $type): self {
        return match (strtolower($type)) {
            'default', 'boxing' => new DefaultArenaSetup($type), // BoxingArenaSetup
            strtolower(BridgeArenaProperties::IDENTIFIER) => new BridgeArenaSetup(),
            strtolower(BedFightArenaProperties::IDENTIFIER) => new BedFightArenaSetup(),
            strtolower(EventArenaProperties::IDENTIFIER) => new EventArenaSetup(),
            default => throw new InvalidArgumentException('Invalid arena setup type ' . $type),
        };
    }
}