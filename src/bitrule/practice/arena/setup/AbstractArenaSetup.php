<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\manager\ArenaManager;
use pocketmine\math\Vector3;

abstract class AbstractArenaSetup {

    /** @var string|null */
    private ?string $name = null;

    /** @var Vector3|null */
    private ?Vector3 $firstPosition = null;
    /** @var Vector3|null */
    private ?Vector3 $secondPosition = null;

    /**
     * @return string|null
     */
    public function getName(): ?string {
        return $this->name;
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
     * Returns the type of arena setup.
     *
     * @return string
     */
    abstract public function getType(): string;

    /**
     * This method is called when the arena is submitted to the arena manager.
     * This is where you should set the arena's properties.
     *
     * @param AbstractArena $arena
     */
    public function submit(AbstractArena $arena): void {
        if ($this->name === null) {
            throw new \RuntimeException('Arena name is not set');
        }

        if ($this->firstPosition === null) {
            throw new \RuntimeException('First position is not set');
        }

        if ($this->secondPosition === null) {
            throw new \RuntimeException('Second position is not set');
        }

        $arena->setFirstPosition($this->firstPosition);
        $arena->setSecondPosition($this->secondPosition);
    }

    /**
     * @param string $type
     *
     * @return AbstractArenaSetup
     */
    public static function from(string $type): AbstractArenaSetup {
        return match (strtolower($type)) {
            'normal' => new DefaultArenaSetup(),
            'bridge' => new BridgeArenaSetup(),
            default => throw new \InvalidArgumentException('Invalid arena setup type ' . $type),
        };
    }
}