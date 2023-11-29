<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use pocketmine\math\Vector3;

class NormalArenaSetup {

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

    public function submit(): void {}
}