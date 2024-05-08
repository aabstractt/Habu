<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\arena\listener\AnythingDamageArenaListener;
use bitrule\practice\arena\listener\AttackDamageArenaListener;
use bitrule\practice\duel\Duel;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;

final class PlayingStage implements AbstractStage {

    /** @var int */
    private int $seconds = 0;

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param Duel $duel
     */
    public function update(Duel $duel): void {
        if (!$duel->isLoaded()) {
            throw new \RuntimeException('Match is not loaded.');
        }

        $this->seconds++;
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
        $arena = $duel->getArena();
        if (!$arena instanceof AnythingDamageArenaListener) return;

        $arena->onAnythingDamageEvent($duel, $victim, $ev);
    }

    /**
     * This method is called when a player is damaged by another player.
     *
     * @param Duel                      $duel
     * @param Player                    $victim
     * @param EntityDamageByEntityEvent $ev
     */
    public function onEntityDamageByEntityEvent(Duel $duel, Player $victim, EntityDamageByEntityEvent $ev): void {
        $victimProfile = $duel->getPlayer($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $attacker = $ev->getDamager();
        if (!$attacker instanceof Player) return;

        $attackerProfile = $duel->getPlayer($attacker->getXuid());
        if ($attackerProfile === null || !$attackerProfile->isAlive()) return;

        $attackerDuelStatistics = $attackerProfile->getDuelStatistics();
        $attackerDuelStatistics->increaseDamageDealt($ev->getFinalDamage());
        $attackerDuelStatistics->increaseTotalHits();

        $arena = $duel->getArena();
        if (!$arena instanceof AttackDamageArenaListener) return;

        $arena->onEntityDamageByEntityEvent($duel, $victim, $ev);
    }

    /**
     * @return int
     */
    public function getSeconds(): int {
        return $this->seconds;
    }
}