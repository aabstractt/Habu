<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelScoreboard;
use bitrule\practice\duel\properties\FireballFightProperties;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\profile\LocalProfile;
use bitrule\practice\TranslationKey;
use LogicException;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class FireballFightPlayingStage extends PlayingStage implements AnythingDamageStageListener, AttackDamageStageListener, DuelScoreboard {

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
        if ($victim->getHealth() - $ev->getFinalDamage() > 0) return;

        $victimProfile = $duel->getPlayer($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $attacker = $ev instanceof EntityDamageByEntityEvent ? $ev->getDamager() : null;

        $attackerProfile = $attacker instanceof Player ? $duel->getPlayer($attacker->getXuid()) : null;
        if ($attacker !== null && ($attackerProfile === null || !$attackerProfile->isAlive())) return;

        $victimSpawnId = $duel->getSpawnId($victim->getXuid());
        if ($victimSpawnId > 1) {
            throw new LogicException('Invalid spawn id: ' . $victimSpawnId);
        }

        $properties = $duel->getProperties();
        if (!$properties instanceof FireballFightProperties) {
            throw new LogicException('Invalid properties');
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
}