<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\arena\asyncio\FileDeleteAsyncTask;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\NormalDuelImpl;
use bitrule\practice\duel\impl\round\NormalRoundingDuelImpl;
use bitrule\practice\duel\impl\round\RoundingDuel;
use bitrule\practice\duel\impl\round\RoundingInfo;
use bitrule\practice\kit\Kit;
use bitrule\practice\match\MatchRounds;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_filter;
use function array_map;
use function array_sum;
use function count;

final class DuelRegistry {
    use SingletonTrait;

    /** @var array<string, Duel> */
    private array $duels = [];
    /** @var int */
    private int $duelsPlayed = 0;
    /** @var array<string, string> */
    private array $playersDuel = [];

    /**
     * @param Player[]           $players
     * @param Player[]           $spectators
     * @param Kit                $kit
     * @param bool               $ranked
     * @param RoundingInfo|null  $roundingInfo
     * @param AbstractArena|null $arena
     *
     * @return Duel
     */
    public function createDuel(
        array $players,
        array $spectators,
        Kit $kit,
        bool $ranked,
        ?RoundingInfo $roundingInfo = null,
        ?AbstractArena $arena = null
    ): Duel {
        $arena ??= ArenaRegistry::getInstance()->getRandomArena($kit);
        if ($arena === null) {
            throw new RuntimeException('No arenas available for duel type: ' . $kit->getName());
        }

        if ($roundingInfo === null) {
            $duel = new NormalDuelImpl($arena, $kit, $this->duelsPlayed++, $ranked);
        } else {
            $duel = new NormalRoundingDuelImpl($arena, $kit, $roundingInfo, $this->duelsPlayed++, $ranked);
        }

        // TODO: Cache the player duel to prevent make many iterations for only a player
        // that helps a bit with the performance
        foreach ($players as $player) {
            $this->playersDuel[$player->getXuid()] = $duel->getFullName();
        }

        // TODO: Copy the world from the backup to the worlds folder
        // after that, load the world and prepare our duel!
        ArenaRegistry::getInstance()->loadWorld(
            $arena->getName(),
            $duel->getFullName(),
            function() use ($spectators, $players, $duel): void {
                $duel->prepare($players);

                foreach ($spectators as $spectator) $duel->joinSpectator($spectator);
            }
        );

        $this->duels[$duel->getFullName()] = $duel;

        return $duel;
    }

    /**
     * @param Duel $duel
     */
    public function endDuel(Duel $duel): void {
        unset($this->duels[$duel->getFullName()]);

        // Unload the world and delete the folder.
        Server::getInstance()->getWorldManager()->unloadWorld($duel->getWorld());

        if ($duel instanceof RoundingDuel) return;

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
        $duelName = $this->playersDuel[$xuid] ?? null;
        if ($duelName === null) return null;

        return $this->duels[$duelName] ?? null;
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