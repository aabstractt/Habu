<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\arena\ScalableArena;
use bitrule\practice\kit\Kit;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\impl\SingleMatchImpl;
use bitrule\practice\match\impl\TeamMatchImpl;
use pocketmine\player\Player;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class MatchManager {
    use SingletonTrait;

    /** @var array<string, AbstractMatch> */
    private array $matches = [];

    /**
     * @param Player[] $totalPlayers
     * @param Kit      $kit
     * @param bool     $team
     * @param bool     $ranked
     */
    public function createMatch(array $totalPlayers, Kit $kit, bool $team, bool $ranked): void {
        $arena = ArenaManager::getInstance()->getRandomArena($kit);
        if ($arena === null) {
            throw new RuntimeException('No arenas available for duel type: ' . $kit->getName());
        }

        if (!$arena instanceof ScalableArena) {
            throw new RuntimeException('Arena is not scalable: ' . $arena->getName());
        }

        $gridIndex = $arena->getAvailableGrid();
        if ($gridIndex <= 0) {
            throw new RuntimeException('No grids available for arena: ' . $arena->getName());
        }

        $arena->removeAvailableGrid($gridIndex);

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
        $duelProfile = ProfileManager::getInstance()->getDuelProfile($xuid);
        if ($duelProfile === null) return null;

        return $this->matches[$duelProfile->getMatchFullName()] ?? null;
    }
}