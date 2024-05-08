<?php

declare(strict_types=1);

namespace bitrule\practice\kit;

use bitrule\practice\profile\LocalProfile;
use InvalidArgumentException;
use pocketmine\entity\Attribute;
use pocketmine\entity\Entity;
use pocketmine\player\Player;
use function mt_getrandmax;
use function mt_rand;
use function sqrt;
use function var_dump;

final class KnockbackProfile {

    /**
     * @param string $name
     * @param float  $horizontal
     * @param float  $vertical
     * @param float  $highestLimit
     * @param int    $hitDelay
     */
    public function __construct(
        private readonly string $name,
        private float $horizontal,
        private float $vertical,
        private float $highestLimit,
        private int $hitDelay,
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * The horizontal value of the knockback profile.
     *
     * @return float
     */
    public function getHorizontal(): float {
        return $this->horizontal;
    }

    /**
     * @param float $horizontal
     */
    public function setHorizontal(float $horizontal): void {
        $this->horizontal = $horizontal;
    }

    /**
     * @return float
     */
    public function getVertical(): float {
        return $this->vertical;
    }

    /**
     * @param float $vertical
     */
    public function setVertical(float $vertical): void {
        $this->vertical = $vertical;
    }

    /**
     * The highest limit of the knockback profile.
     * When the knockback value is higher than this value, it will be set to this value.
     *
     * @return float
     */
    public function getHighestLimit(): float {
        return $this->highestLimit;
    }

    /**
     * @param float $highestLimit
     */
    public function setHighestLimit(float $highestLimit): void {
        $this->highestLimit = $highestLimit;
    }

    /**
     * @return int
     */
    public function getHitDelay(): int {
        return $this->hitDelay;
    }

    /**
     * @param int $hitDelay
     */
    public function setHitDelay(int $hitDelay): void {
        $this->hitDelay = $hitDelay;
    }

    /**
     * Applies the knockback profile to the given player.
     *
     * @param Player       $victim
     * @param LocalProfile $victimProfile
     * @param Entity|null  $attacker
     */
    public function applyOn(Player $victim, LocalProfile $victimProfile, ?Entity $attacker): void {
        if ($attacker === null) {
            throw new InvalidArgumentException('Attacker cannot be null');
        }

        $victimPosition = $victim->getPosition();
        $attackerPosition = $attacker->getPosition();

        $verticalKb = $this->vertical;
        $horizontalKb = $this->horizontal;
        if ($verticalKb === 0.0 && $horizontalKb === 0.0) {
            throw new InvalidArgumentException('Both vertical and horizontal knockback values cannot be 0');
        }

        if ($this->highestLimit > 0.0 && !$victim->isOnGround()) {
            $dist = $victimPosition->getY() > $attackerPosition->getY() ? $victimPosition->getY() - $attackerPosition->getY() : $attackerPosition->getY() - $victimPosition->getY();
            if ($dist > $this->highestLimit) {
                $verticalKb *= 0.73;
            }
        }

        $diffX = $victimPosition->getX() - $attackerPosition->getX();
        $diffZ = $victimPosition->getZ() - $attackerPosition->getZ();

        $force = sqrt($diffX * $diffX + $diffZ * $diffZ);
        if ($force <= 0) {
            echo 'Force is less than or equal to 0' . PHP_EOL;

            return;
        }

        $attribute = $victim->getAttributeMap()->get(Attribute::KNOCKBACK_RESISTANCE);
        if ($attribute === null) {
            throw new \RuntimeException('Victim does not have attack damage attribute');
        }

        if (mt_rand() / mt_getrandmax() <= $attribute->getValue()) {
            echo 'Critical hit' . PHP_EOL;

            return;
        }

        $force = 1 / $force;
        $motion = clone $victim->getMotion();

        $motion->x /= 2;
        $motion->y /= 2;
        $motion->z /= 2;

        $motion->x += $diffX * $force * $horizontalKb;
        $motion->y += $verticalKb;
        $motion->z += $diffZ * $force * $horizontalKb;

        if ($motion->y > $verticalKb) {
            $motion->y = $verticalKb;
        }

        $victimProfile->initialKnockbackMotion = true;
        $victim->setMotion($motion);

        var_dump($motion);

        echo 'Applied knockback' . PHP_EOL;
    }

    /**
     * Creates an empty knockback profile with the given name.
     *
     * @param string $name
     *
     * @return self
     */
    public static function empty(string $name): self {
        return new self($name, 0.0, 0.0, 0.0, -1);
    }
}