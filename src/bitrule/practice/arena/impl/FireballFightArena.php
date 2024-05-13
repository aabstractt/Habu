<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\listener\AnythingDamageArenaListener;
use bitrule\practice\arena\listener\AttackDamageArenaListener;
use bitrule\practice\arena\ScoreboardId;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\properties\FireballFightProperties;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\profile\LocalProfile;
use bitrule\practice\TranslationKey;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function array_merge;

final class FireballFightArena extends AbstractArena implements AttackDamageArenaListener, AnythingDamageArenaListener, ScoreboardId {

    public const NAME = 'fireball_fight';

    /**
     * @param string  $name
     * @param Vector3 $firstPosition
     * @param Vector3 $firstBedPosition
     * @param Vector3 $secondPosition
     * @param Vector3 $secondBedPosition
     */
    public function __construct(
        string $name,
        Vector3 $firstPosition,
        private Vector3 $firstBedPosition,
        Vector3 $secondPosition,
        private Vector3 $secondBedPosition
    ) {
        parent::__construct($name, $firstPosition, $secondPosition, [self::NAME]);
    }

    /**
     * @return Vector3
     */
    public function getFirstBedPosition(): Vector3 {
        return $this->firstBedPosition;
    }

    /**
     * This is the first bed position of the arena. (Red team)
     *
     * @param Vector3 $firstBedPosition
     */
    public function setFirstBedPosition(Vector3 $firstBedPosition): void {
        $this->firstBedPosition = $firstBedPosition;
    }

    /**
     * @return Vector3
     */
    public function getSecondBedPosition(): Vector3 {
        return $this->secondBedPosition;
    }

    /**
     * This is the second bed position of the arena. (Blue team)
     *
     * @param Vector3 $secondBedPosition
     */
    public function setSecondBedPosition(Vector3 $secondBedPosition): void {
        $this->secondBedPosition = $secondBedPosition;
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(): array {
        return array_merge(
            parent::serialize(),
            [
            	'type' => self::NAME,
            	'first_bed_position' => self::serializeVector($this->firstBedPosition),
            	'second_bed_position' => self::serializeVector($this->secondBedPosition)
            ]
        );
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return self
     */
    protected static function parse(string $name, array $data): self {
        return new self(
            $name,
            self::deserializeVector($data['first_position'] ?? []),
            self::deserializeVector($data['first_bed_position'] ?? []),
            self::deserializeVector($data['second_position'] ?? []),
            self::deserializeVector($data['second_bed_position'] ?? [])
        );
    }

    /**
     * @param string $name
     *
     * @return self
     */
    protected static function parseEmpty(string $name): self {
        return new self(
            $name,
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero()
        );
    }

    /**
     * This method is called when a player is damaged by another player.
     *
     * @param Duel                      $duel
     * @param Player                    $victim
     * @param EntityDamageByEntityEvent $ev
     */
    public function onEntityDamageByEntityEvent(Duel $duel, Player $victim, EntityDamageByEntityEvent $ev): void {
        $this->onAnythingDamageEvent($duel, $victim, $ev);
    }

    /**
     * This method is called when a player is damaged by anything
     * except another player.
     *
     * @param Duel              $duel
     * @param Player            $victim
     * @param EntityDamageEvent $ev
     */
    public function onAnythingDamageEvent(Duel $duel, Player $victim, EntityDamageEvent $ev): void {
        if (!$duel->getStage() instanceof PlayingStage) {
            $ev->cancel();

            return;
        }

        if ($victim->getHealth() - $ev->getFinalDamage() > 0) return;

        $victimProfile = $duel->getPlayer($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $attacker = $ev instanceof EntityDamageByEntityEvent ? $ev->getDamager() : null;

        $attackerProfile = $attacker instanceof Player ? $duel->getPlayer($attacker->getXuid()) : null;
        if ($attacker !== null && ($attackerProfile === null || !$attackerProfile->isAlive())) return;

        $victimSpawnId = $duel->getSpawnId($victim->getXuid());
        if ($victimSpawnId > 1) {
            throw new \LogicException('Invalid spawn id: ' . $victimSpawnId);
        }

        $properties = $duel->getProperties();
        if (!$properties instanceof FireballFightProperties) {
            throw new \LogicException('Invalid properties');
        }

        $ev->cancel();

        $duel->teleportSpawn($victim);
        LocalProfile::resetInventory($victim);

        $colorSupplier = fn(int $spawnId): string => $spawnId === 0 ? TextFormat::RED : TextFormat::BLUE;
        if ($attackerProfile === null || $attacker === null) {
            $duel->broadcastMessage(TranslationKey::FIREBALL_FIGHT_PLAYER_DEAD_WITHOUT_KILLER()->build(
                $colorSupplier($victimSpawnId) . $victim->getName()
            ));
        } else {
            $duel->broadcastMessage(TranslationKey::FIREBALL_FIGHT_PLAYER_DEAD()->build(
                $colorSupplier($victimSpawnId) . $victim->getName(),
                $colorSupplier($duel->getSpawnId($attackerProfile->getXuid())) . $attackerProfile->getName()
            ));
        }

        $hasBeenBedDestroyed = $victimSpawnId === 0 ? $properties->isRedBedDestroyed() : $properties->isBlueBedDestroyed();
        if ($hasBeenBedDestroyed) {
            $victimProfile->convertAsSpectator($duel, false);
        } else {
            $duel->getKit()->applyOn($victim);
        }
    }

    /**
     * This is the scoreboard identifier of the arena.
     *
     * @return string
     */
    public function getScoreboardId(): string {
        return 'match-playing-fireball';
    }

    /**
     * Replace placeholders in the text.
     *
     * @param Duel         $duel
     * @param Player       $source
     * @param LocalProfile $localProfile
     * @param string       $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Duel $duel, Player $source, LocalProfile $localProfile, string $identifier): ?string {
        return '';
    }
}