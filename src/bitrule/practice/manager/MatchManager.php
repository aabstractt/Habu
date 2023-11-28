<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\impl\TeamMatchImpl;
use pocketmine\utils\SingletonTrait;

final class MatchManager {
    use SingletonTrait;

    public function createMatch(string $duelType, array $totalPlayers, bool $team): void {
        $kit = KitManager::getInstance()->getKit($duelType);
        if ($kit === null) {
            throw new \InvalidArgumentException('Invalid duel type: ' . $duelType);
        }

        $arena = ArenaManager::getInstance()->getRandomArena($duelType);
        if ($arena === null) {
            throw new \RuntimeException('No arenas available for duel type: ' . $duelType);
        }

        if ($team) {
            $match = new TeamMatchImpl();
        } else {
            $match = new SingleMatchImpl();
        }
    }
}