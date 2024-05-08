<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\AttackDamageArena;
use bitrule\practice\duel\Duel;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\math\Vector3;
use pocketmine\player\Player;

final class BoxingArena extends AbstractArena implements AttackDamageArena {

    /**
     * This method is called when a player is damaged by another player.
     *
     * @param Duel                      $duel
     * @param Player                    $victim
     * @param EntityDamageByEntityEvent $ev
     */
    public function onEntityDamageByEntityEvent(Duel $duel, Player $victim, EntityDamageByEntityEvent $ev): void {
        $attacker = $ev->getDamager();
        if (!$attacker instanceof Player) return;

        $attackerProfile = $duel->getPlayer($attacker->getXuid());
        if ($attackerProfile === null || !$attackerProfile->isAlive()) return;

        $attackerDuelStatistics = $attackerProfile->getDuelStatistics();
        if ($attackerDuelStatistics->getTotalHits() < 100) return;

        $victimProfile = $duel->getPlayer($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $victimProfile->convertAsSpectator($duel, false);
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(): array {
        $serialized = parent::serialize();
        $serialized['type'] = 'boxing';

        return $serialized;
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return BoxingArena
     */
    protected static function parse(string $name, array $data): self {
        return new self(
            $name,
            self::deserializeVector($data['first_position'] ?? []),
            self::deserializeVector($data['second_position'] ?? []),
            $data['kits'] ?? []
        );
    }

    /**
     * @param string $name
     *
     * @return BoxingArena
     */
    protected static function parseEmpty(string $name): self {
        return new self(
            $name,
            Vector3::zero(),
            Vector3::zero(),
            []
        );
    }
}