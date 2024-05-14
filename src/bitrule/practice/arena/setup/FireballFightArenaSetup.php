<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\impl\FireballFightArenaProperties;
use InvalidArgumentException;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;

final class FireballFightArenaSetup extends AbstractArenaSetup {

    /** @var Vector3|null $firstBedPosition */
    private ?Vector3 $firstBedPosition = null;
    /** @var Vector3|null */
    private ?Vector3 $secondBedPosition = null;

    /**
     * @param int      $step
     * @param Location $position
     */
    public function setPositionByStep(int $step, Location $position): void {
        if ($step < 2) {
            parent::setPositionByStep($step, $position);
        } elseif ($step === 2) {
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
     * @return array
     */
    public function getProperties(): array {
        if ($this->firstBedPosition === null || $this->secondBedPosition === null) {
            throw new InvalidArgumentException('Bed positions are not set');
        }

        $properties = parent::getProperties();
        $properties['first-bed-position'] = $this->firstBedPosition;
        $properties['second-bed-position'] = $this->secondBedPosition;

        return $properties;
    }

    /**
     * Loads the arena properties.
     *
     * @param ArenaProperties $properties
     */
    public function load(ArenaProperties $properties): void {
        parent::load($properties);

        if (!$properties instanceof FireballFightArenaProperties) {
            throw new InvalidArgumentException('Invalid arena properties');
        }

        $this->firstBedPosition = $properties->getFirstBedPosition();
        $this->secondBedPosition = $properties->getSecondBedPosition();
    }

    /**
     * Returns the type of arena setup.
     *
     * @return string
     */
    public function getType(): string {
        return FireballFightArenaProperties::IDENTIFIER;
    }
}