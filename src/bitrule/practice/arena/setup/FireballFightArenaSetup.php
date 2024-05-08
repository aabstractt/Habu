<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\impl\FireballFightArena;
use InvalidArgumentException;
use pocketmine\math\Vector3;

final class FireballFightArenaSetup extends AbstractArenaSetup {

    /** @var Vector3|null $firstBedPosition */
    private ?Vector3 $firstBedPosition = null;
    /** @var Vector3|null */
    private ?Vector3 $secondBedPosition = null;

    /**
     * @param int     $step
     * @param Vector3 $position
     */
    public function setPositionByStep(int $step, Vector3 $position): void {
        if ($step < 3) {
            parent::setPositionByStep($step, $position);
        } elseif ($step === 3) {
            $this->firstBedPosition = $position;
        } else {
            $this->secondBedPosition = $position;
        }
    }

    /**
     * Increases the spawn step by 1.
     */
    public function increaseSpawnStep(): void {
        parent::increaseSpawnStep();

        if ($this->spawnStep < 4) return;

        $this->spawnStep = 0;
    }

    /**
     * This method is called when the arena is created into the arena manager.
     * This is where you should set the arena's properties.
     *
     * @param AbstractArena $arena
     */
    public function submit(AbstractArena $arena): void {
        if (!$arena instanceof FireballFightArena) {
            throw new InvalidArgumentException('Arena must be a FireballFightArena');
        }

        if ($this->firstBedPosition === null || $this->secondBedPosition === null) {
            throw new InvalidArgumentException('Bed positions are not set');
        }

        $arena->setFirstBedPosition($this->firstBedPosition);
        $arena->setSecondBedPosition($this->secondBedPosition);

        parent::submit($arena);
    }

    /**
     * Returns the type of arena setup.
     *
     * @return string
     */
    public function getType(): string {
        return FireballFightArena::NAME;
    }
}