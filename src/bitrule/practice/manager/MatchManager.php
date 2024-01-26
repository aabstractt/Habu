<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

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
    /** @var int */
    private int $matchesPlayed = 0;

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

        if ($team) {
            $match = new TeamMatchImpl($arena, $this->matchesPlayed++, $ranked);
        } else {
            $match = new SingleMatchImpl($arena, $this->matchesPlayed++, $ranked);
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