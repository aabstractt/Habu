<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\asyncio\FileDeleteAsyncTask;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\NormalDuelImpl;
use bitrule\practice\kit\Kit;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\MatchRounds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_sum;
use function count;

final class DuelManager {
    use SingletonTrait;

    /** @var array<string, Duel> */
    private array $duels = [];
    /** @var int */
    private int $duelsPlayed = 0;
    /** @var array<string, string> */
    private array $playersDuel = [];

    /**
     * Create a Duel for rounding.
     * This is used for tournaments.
     * Or any other time you want to play multiple Duel on the same arena.
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

        $duel = new NormalDuelImpl($arena, $kit, $this->duelsPlayed++, $ranked);

        // TODO: Cache the player duel to prevent make many iterations for only a player
        // that helps a bit with the performance
        foreach ($players as $player) {
            $this->playersDuel[$player->getXuid()] = $duel->getFullName();
        }

        // TODO: Copy the world from the backup to the worlds folder
        // after that, load the world and prepare our duel!
        ArenaManager::getInstance()->loadWorld(
            $arena->getName(),
            $duel->getFullName(),
            function() use ($spectators, $players, $duel): void {
                $duel->prepare($players);

                foreach ($spectators as $spectator) $duel->joinSpectator($spectator);
            }
        );

        $this->duels[$duel->getFullName()] = $duel;
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
//            $match = new TeamDuelImpl($arena, $kit, $this->matchesPlayed++, $ranked);
//        } else {
//            $match = new NormalDuelImpl($arena, $kit, $this->matchesPlayed++, $ranked);
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
     * @param Duel $duel
     */
    public function endMatch(Duel $duel): void {
        unset($this->duels[$duel->getFullName()]);

        // Unload the world and delete the folder.
        Server::getInstance()->getWorldManager()->unloadWorld($duel->getWorld());
        Server::getInstance()->getAsyncPool()->submitTask(new FileDeleteAsyncTask(
            Server::getInstance()->getDataPath() . 'worlds/' . $duel->getFullName()
        ));
    }

    /**
     * @param string $sourceXuid
     */
    public function quitPlayer(string $sourceXuid): void {
        unset($this->playersDuel[$sourceXuid]);
    }

    /**
     * @param string $xuid
     *
     * @return Duel|null
     */
    public function getDuelByPlayer(string $xuid): ?Duel {
        $duelProfile = ProfileManager::getInstance()->getDuelProfile($xuid);
        if ($duelProfile === null) return null;

        return $this->duels[$duelProfile->getMatchFullName()] ?? null;
    }

    /**
     * @param string|null $kitName
     *
     * @return int
     */
    public function getDuelsCount(?string $kitName = null): int {
        $duels = $this->duels;
        if ($kitName !== null) {
            $duels = array_filter(
                $duels,
                fn(Duel $duel) => $duel->getArena()->hasKit($kitName)
            );
        }

        return array_sum(
            array_map(
                fn(Duel $duel) => count($duel->getAlive()),
                $duels
            )
        );
    }

    /**
     * Tick all duels
     */
    public function tickStages(): void {
        foreach ($this->duels as $duel) {
            $duel->getStage()->update($duel);
        }
    }
}