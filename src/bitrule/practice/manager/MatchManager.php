<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\impl\SingleMatchImpl;
use bitrule\practice\match\impl\TeamMatchImpl;
use InvalidArgumentException;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class MatchManager {
    use SingletonTrait;

    /** @var array<string, AbstractMatch> */
    private array $matches = [];

    /**
     * @param string $duelType
     * @param array  $totalPlayers
     * @param bool   $team
     * @param bool   $ranked
     */
    public function createMatch(string $duelType, array $totalPlayers, bool $team, bool $ranked): void {
        $kit = KitManager::getInstance()->getKit($duelType);
        if ($kit === null) {
            throw new InvalidArgumentException('Invalid duel type: ' . $duelType);
        }

        $arena = ArenaManager::getInstance()->getRandomArena($duelType);
        if ($arena === null) {
            throw new RuntimeException('No arenas available for duel type: ' . $duelType);
        }

        $gridIndex = $arena->getSchematic()->getAvailableGrid();
        if ($gridIndex <= 0) {
            throw new RuntimeException('No grids available for arena: ' . $arena->getSchematic()->getName());
        }

        $arena->getSchematic()->removeAvailableGrid($gridIndex);

        if ($team) {
            $match = new TeamMatchImpl($gridIndex, $arena, $ranked);
        } else {
            $match = new SingleMatchImpl($gridIndex, $arena, $ranked);
        }

        $match->setup($totalPlayers);
        $this->matches[$match->getFullName()] = $match;
    }

    /**
     * @param string $xuid
     *
     * @return AbstractMatch|null
     */
    public function getPlayerMatch(string $xuid): ?AbstractMatch {
        $duelPlayer = PlayerManager::getInstance()->getDuelPlayer($xuid);
        if ($duelPlayer === null) return null;

        return $this->matches[$duelPlayer->getMatchFullName()] ?? null;
    }
}