<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\asyncio\FileDeleteAsyncTask;
use bitrule\practice\kit\Kit;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\impl\SingleMatchImpl;
use bitrule\practice\match\impl\TeamMatchImpl;
use bitrule\practice\match\MatchRounds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_sum;
use function count;

final class MatchManager {
    use SingletonTrait;

    /** @var array<string, AbstractMatch> */
    private array $matches = [];
    /** @var int */
    private int $matchesPlayed = 0;

    /**
     * Create a match for rounding.
     * This is used for tournaments.
     * Or any other time you want to play multiple matches on the same arena.
     *
     * @param Player[]           $players
     * @param Player[]           $spectators
     * @param Kit                $kit
     * @param AbstractArena|null $arena
     * @param MatchRounds        $matchRounds
     * @param bool               $ranked
     */
    public function createMatchForRounding(
        array $players,
        array $spectators,
        Kit $kit,
        ?AbstractArena $arena,
        MatchRounds $matchRounds,
        bool $ranked
    ): void {
        $arena ??= ArenaManager::getInstance()->getRandomArena($kit);
        if ($arena === null) {
            throw new RuntimeException('No arenas available for duel type: ' . $kit->getName());
        }

        $match = new SingleMatchImpl($arena, $kit, $this->matchesPlayed++, $ranked, $matchRounds);
        $match->prepare($players);

        ArenaManager::getInstance()->loadWorld(
            $arena->getName(),
            $match->getFullName(),
            function() use ($spectators, $players, $match): void {
                $match->postPrepare($players);

                foreach ($spectators as $spectator) $match->joinSpectator($spectator);
            }
        );

        $this->matches[$match->getFullName()] = $match;
    }

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

        $this->createMatchForRounding(
            $totalPlayers,
            [],
            $kit,
            $arena,
            new MatchRounds(1, 3),
            $ranked
        );

//        if ($team) {
//            $match = new TeamMatchImpl($arena, $kit, $this->matchesPlayed++, $ranked);
//        } else {
//            $match = new SingleMatchImpl($arena, $kit, $this->matchesPlayed++, $ranked);
//        }
//
//        $match->prepare($totalPlayers);
//
//        ArenaManager::getInstance()->loadWorld(
//            $arena->getName(),
//            $match->getFullName(),
//            fn() => $match->postPrepare($totalPlayers)
//        );
//
//        $this->matches[$match->getFullName()] = $match;
    }

    /**
     * @param AbstractMatch $match
     */
    public function endMatch(AbstractMatch $match): void {
        unset($this->matches[$match->getFullName()]);

        // Unload the world and delete the folder.
        Server::getInstance()->getWorldManager()->unloadWorld($match->getWorld());
        Server::getInstance()->getAsyncPool()->submitTask(new FileDeleteAsyncTask(
            Server::getInstance()->getDataPath() . 'worlds/' . $match->getFullName()
        ));
    }

    /**
     * @param string $xuid
     *
     * @return AbstractMatch|null
     */
    public function getMatchByPlayer(string $xuid): ?AbstractMatch {
        $duelProfile = ProfileManager::getInstance()->getDuelProfile($xuid);
        if ($duelProfile === null) return null;

        return $this->matches[$duelProfile->getMatchFullName()] ?? null;
    }

    /**
     * @param string|null $kitName
     *
     * @return int
     */
    public function getMatchCount(?string $kitName = null): int {
        $matches = $this->matches;
        if ($kitName !== null) {
            $matches = array_filter($matches, fn(AbstractMatch $match) => $match->getArena()->hasKit($kitName));
        }

        return array_sum(array_map(
                fn(AbstractMatch $match) => count($match->getAlive()),
                $matches)
        );
    }

    /**
     * Tick all matches
     */
    public function tickStages(): void {
        foreach ($this->matches as $match) {
            $match->getStage()->update($match);
        }
    }
}