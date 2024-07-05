<?php

declare(strict_types=1);

namespace bitrule\practice\arena\setup;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\impl\BedFightArenaProperties;
use bitrule\practice\arena\impl\EventArenaProperties;
use InvalidArgumentException;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;

final class EventArenaSetup extends AbstractArenaSetup {

    /** @var Vector3|null $firstFightCorner */
    private ?Vector3 $firstFightCorner = null;
    /** @var Vector3|null */
    private ?Vector3 $secondFightCorner = null;

    /**
     * @param int      $step
     * @param Location $position
     */
    public function setPositionByStep(int $step, Location $position): void {
        if ($step < 2) {
            parent::setPositionByStep($step, $position);
        } elseif ($step === 2) {
            $this->firstFightCorner = $position;
        } else {
            $this->secondFightCorner = $position;
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
        if ($this->firstFightCorner === null || $this->secondFightCorner === null) {
            throw new InvalidArgumentException('Bed positions are not set');
        }

        $properties = parent::getProperties();
        $properties['first-fight-corner'] = $this->firstFightCorner;
        $properties['second-fight-corner'] = $this->secondFightCorner;

        return $properties;
    }

    /**
     * Loads the arena properties.
     *
     * @param ArenaProperties $properties
     */
    public function load(ArenaProperties $properties): void {
        parent::load($properties);

        if (!$properties instanceof BedFightArenaProperties) {
            throw new InvalidArgumentException('Invalid arena properties');
        }

        $this->firstFightCorner = $properties->getFirstBedPosition();
        $this->secondFightCorner = $properties->getSecondBedPosition();
    }

    /**
     * Returns the type of arena setup.
     *
     * @return string
     */
    public function getType(): string {
        return EventArenaProperties::IDENTIFIER;
    }
}