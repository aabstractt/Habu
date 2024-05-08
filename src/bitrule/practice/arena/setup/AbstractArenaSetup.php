<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\impl\FireballFightArena;
use bitrule\practice\Practice;
use bitrule\practice\registry\ArenaRegistry;
use InvalidArgumentException;
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

    /** @var Vector3|null */
    private ?Vector3 $firstPosition = null;
    /** @var Vector3|null */
    private ?Vector3 $secondPosition = null;
    /** @var string[] */
    private array $kits = [];

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
     * @return Vector3|null
     */
    public function getFirstPosition(): ?Vector3 {
        return $this->firstPosition;
    }

    /**
     * @param Vector3|null $firstPosition
     */
    public function setFirstPosition(?Vector3 $firstPosition): void {
        $this->firstPosition = $firstPosition;
    }

    /**
     * @return Vector3|null
     */
    public function getSecondPosition(): ?Vector3 {
        return $this->secondPosition;
    }

    /**
     * @param Vector3|null $secondPosition
     */
    public function setSecondPosition(?Vector3 $secondPosition): void {
        $this->secondPosition = $secondPosition;
    }

    /**
     * @param string $kitName
     */
    public function addKit(string $kitName): void {
        $this->kits[] = $kitName;
    }

    /**
     * @param int     $step
     * @param Vector3 $position
     */
    public function setPositionByStep(int $step, Vector3 $position): void {
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

        if (ArenaRegistry::getInstance()->getArena($this->name) !== null) {
            throw new RuntimeException('Arena ' . $this->name . ' already exists');
        }

        $worldManager = Server::getInstance()->getWorldManager();
        if (!$worldManager->isWorldGenerated($this->name)) {
            Practice::getInstance()->getLogger()->info('Generating world ' . $this->name);
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

        $this->started = true;
    }

    /**
     * This method is called when the arena is created into the arena manager.
     * This is where you should set the arena's properties.
     *
     * @param AbstractArena $arena
     */
    public function submit(AbstractArena $arena): void {
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

        $arena->setFirstPosition($this->firstPosition);
        $arena->setSecondPosition($this->secondPosition);
        $arena->setKits($this->kits);
    }

    /**
     * @param string $type
     *
     * @return AbstractArenaSetup
     */
    public static function from(string $type): self {
        return match (strtolower($type)) {
            'normal', 'boxing' => new DefaultArenaSetup($type), // BoxingArenaSetup
            'bridge' => new BridgeArenaSetup(),
            FireballFightArena::NAME => new FireballFightArenaSetup(),
            default => throw new InvalidArgumentException('Invalid arena setup type ' . $type),
        };
    }
}