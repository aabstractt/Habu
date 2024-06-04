<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\TranslationKey;
use LogicException;
use pocketmine\entity\Entity;
use pocketmine\player\Player;

final class DefaultPlayingStage extends KillablePlayingStage {

    /**
     * @param Duel        $duel
     * @param Player      $victim
     * @param Entity|null $attacker
     * @param int         $cause
     */
    public function killPlayer(Duel $duel, Player $victim, ?Entity $attacker, int $cause): void {
        $victimProfile = $duel->getMember($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $attackerProfile = $attacker instanceof Player ? $duel->getMember($attacker->getXuid()) : null;
        if ($attacker !== null && ($attackerProfile === null || !$attackerProfile->isAlive())) return;

        $victimSpawnId = $duel->getSpawnId($victim->getXuid());
        if ($victimSpawnId > 1) {
            throw new LogicException('Invalid spawn id: ' . $victimSpawnId);
        }

        if ($attackerProfile === null || $attacker === null) {
            $duel->broadcastMessage(TranslationKey::DUEL_PLAYER_DEAD_WITHOUT_KILLER()->build(
                $victim->getName()
            ));
        } else {
            $duel->broadcastMessage(TranslationKey::DUEL_PLAYER_DEAD()->build(
                $victim->getName(),
                $attackerProfile->getName()
            ));

            $attackerProfile->getDuelStatistics()->setKills($attackerProfile->getDuelStatistics()->getKills() + 1);
        }

        $victimProfile->convertAsSpectator($duel, false);
    }
}