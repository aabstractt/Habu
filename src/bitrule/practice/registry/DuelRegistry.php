<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\arena\asyncio\FileDeleteAsyncTask;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\round\RoundingDuel;
use bitrule\practice\Habu;
use bitrule\practice\profile\DuelInvite;
use Closure;
use Exception;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use function abs;
use function count;
use function pow;

final class DuelRegistry {
    use SingletonTrait;

    /** @var array<string, Duel> */
    private array $duels = [];
    /** @var array<string, string> */
    private array $playersDuel = [];
    /** @var array<string, Player> */
    private array $playerObject = [];
    /**
     * This array is used to store the duel invites
     * Received by the player
     * @var array<string, DuelInvite[]>>
     */
    private array $duelInvites = [];
    /**
     * This array is used to store the duel invites
     * Sent by the player
     *
     * @var array<string, string[]> $duelInvitesSent
     */
    private array $duelInvitesSent = [];

    /**
     * @param Player[]        $totalPlayers
     * @param Duel         $duel
     * @param ?Closure(Duel): void $onCompletion
     */
    public function prepareDuel(array $totalPlayers, Duel $duel, ?Closure $onCompletion = null): void {
        // Cache the player duel to prevent make many iterations for only a player
        // that helps a bit with the performance
        foreach ($totalPlayers as $player) {
            $this->playersDuel[$player->getXuid()] = $duel->getFullName();
        }

        // Copy the world from the backup to the worlds folder
        // after that, load the world and prepare our duel!
        ArenaRegistry::getInstance()->loadWorld(
            $duel->getArenaProperties()->getOriginalName(),
            $duel->getFullName(),
            function() use ($onCompletion, $totalPlayers, $duel): void {
                try {
                    $duel->prepare($totalPlayers);

                    if ($onCompletion !== null) $onCompletion($duel);
                } catch (Exception $e) {
                    Habu::getInstance()->getLogger()->error('Failed to prepare duel: ' . $e->getMessage());

                    foreach ($totalPlayers as $player) {
                        $player->sendMessage('Failed to prepare duel: ' . $e->getMessage());

                        $this->quitPlayer($player);
                    }
                }
            }
        );

        $this->duels[$duel->getFullName()] = $duel;
    }

    /**
     * @param Duel $duel
     */
    public function endDuel(Duel $duel): void {
        unset($this->duels[$duel->getFullName()]);

        // First check if the world is doing tick
        // if it is, we need to wait until the tick is done
        // to unload the world
        // otherwise, we can unload the world immediately
        // and delete the world file
        $world = $duel->getWorld();
        if ($world->isDoingTick()) {
            Habu::getInstance()->getScheduler()->scheduleTask(
                new ClosureTask(function () use ($duel): void {
                    $this->endDuel($duel);
                })
            );

            return;
        }

        Server::getInstance()->getWorldManager()->unloadWorld($duel->getWorld());

        if ($duel instanceof RoundingDuel) return;

        Server::getInstance()->getAsyncPool()->submitTask(new FileDeleteAsyncTask(
            Server::getInstance()->getDataPath() . 'worlds/' . $duel->getFullName()
        ));
    }

    /**
     * @param Player $source
     */
    public function quitPlayer(Player $source): void {
        $duelId = $this->playersDuel[$source->getXuid()] ?? null;
        if ($duelId === null) return;

        unset($this->playersDuel[$source->getXuid()]);

        $duel = $this->duels[$duelId] ?? null;
        if ($duel === null) return;

        $duel->removePlayer($source);
        $duel->postRemovePlayer($source);
    }

    /**
     * @param string $sourceXuid
     */
    public function disconnectPlayer(string $sourceXuid): void {
        unset($this->playerObject[$sourceXuid]);
    }

    /**
     * @param string $xuid
     *
     * @return Player|null
     */
    public function getPlayerObject(string $xuid): ?Player {
        return $this->playerObject[$xuid] ?? null;
    }

    /**
     * @param Player $player
     */
    public function setPlayerObject(Player $player): void {
        $this->playerObject[$player->getXuid()] = $player;
    }

    /**
     * @param string $sourceXuid Who sent the invite
     * @param string $targetXuid Who received the invite
     */
    public function addDuelInvite(string $sourceXuid, string $targetXuid, string $kitName): void {
        $this->duelInvites[$targetXuid][$sourceXuid] = new DuelInvite($sourceXuid, $kitName, time());
        $this->duelInvitesSent[$sourceXuid][] = $targetXuid;
    }

    /**
     * @param string $sourceXuid Who sent the invite
     * @param string $targetXuid Who received the invite
     */
    public function removeDuelInvite(string $sourceXuid, string $targetXuid): void {
        $duelInvites = $this->duelInvites[$targetXuid] ?? [];
        if (!isset($duelInvites[$sourceXuid])) return;

        unset($duelInvites[$sourceXuid]);
    }

    /**
     * @param string $xuid
     *
     * @return DuelInvite[]
     */
    public function getDuelInvites(string $xuid): array {
        return $this->duelInvites[$xuid] ?? [];
    }

    /**
     * @param string $xuid
     */
    public function clearDuelInvites(string $xuid): void {
        unset($this->duelInvites[$xuid]);

        $xuids = $this->duelInvitesSent[$xuid] ?? [];
        foreach ($xuids as $targetXuid) {
            $this->removeDuelInvite($xuid, $targetXuid);
        }
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
        $playersCounter = 0;

        foreach ($this->duels as $duel) {
            if ($kitName !== null && $duel->getArenaProperties()->getPrimaryKit() !== $kitName) continue;

            $playersCounter += count($duel->getAlive());
        }

        return $playersCounter;
    }

    /**
     * Tick all duels
     */
    public function tickStages(): void {
        foreach ($this->duels as $duel) {
            $duel->getStage()->update($duel);
        }
    }

    /**
     * Calculate the new Elo ratings for a winner and a loser.
     *
     * This method uses the Elo rating system formula to calculate the new ratings for a winner and a loser.
     * The Elo rating system is a method for calculating the relative skill levels of players in zero-sum games such as chess.
     * It is named after its creator Arpad Elo, a Hungarian-American physics professor.
     *
     * @param int $winner The current Elo rating of the winner.
     * @param int $loser The current Elo rating of the loser.
     * @return array An array containing the change in Elo rating for the winner and the loser.
     */
    public static function calculateElo(int $winner, int $loser): array {
        // The expected score of the winner and the loser
        // The expected score is the probability of the winner winning the match.
        // The probability is calculated using the formula 1 / (1 + 10^((L - W) / 400))
        // where L is the Elo rating of the loser and W is the Elo rating of the winner.
        // The expected score of the loser is the absolute value of 1 - the expected score of the winner.
        $expectedScoreA = 1 / (1 + (pow(10, ($loser - $winner) / 400)));
        $expectedScoreB = abs(1 / (1 + pow(10, ($winner - $loser) / 400)));

        $winnerElo = $winner + (int) (32 * (1 - $expectedScoreA));
        $loserElo = $loser + (int) (32 * (0 - $expectedScoreB));

        return [
        	$winnerElo - $winner,
        	abs($loser - $loserElo)
        ];
    }
}