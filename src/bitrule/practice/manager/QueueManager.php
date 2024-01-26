<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\match\MatchQueue;
use bitrule\practice\Practice;
use bitrule\practice\profile\LocalProfile;
use Closure;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_filter;
use function count;
use function time;

final class QueueManager {

    use SingletonTrait;

    /** @var array<string, MatchQueue> */
    private array $queues = [];

    /**
     * Creates a queue for a player
     * Using their xuid, kit name, and if it's ranked
     * Also checks if there is an opponent in the queue
     * If there is, it will remove both players from the queue
     *
     * @param LocalProfile               $sourceLocalProfile
     * @param string                     $kitName
     * @param bool                       $ranked
     * @param ?Closure(MatchQueue): void $onCompletion
     */
    public function createQueue(LocalProfile $sourceLocalProfile, string $kitName, bool $ranked, ?Closure $onCompletion): void {
        if (($kit = KitManager::getInstance()->getKit($kitName)) === null) {
            throw new RuntimeException('Kit no exists.');
        }

        $this->queues[$sourceXuid = $sourceLocalProfile->getXuid()] = $matchQueue = new MatchQueue($sourceXuid, $kitName, $ranked, time());

        $opponentMatchQueue = $this->lookupOpponent($matchQueue);
        if ($opponentMatchQueue === null) {
            if ($onCompletion !== null) {
                $onCompletion($matchQueue);
            }

            return;
        }

        $this->removeQueue($sourceLocalProfile);

        if (($opponentLocalProfile = ProfileManager::getInstance()->getLocalProfile($opponentMatchQueue->getXuid())) === null) {
            throw new RuntimeException('Opponent profile no exists.');
        }

        $this->removeQueue($opponentLocalProfile);

        $totalPlayers = [];
        foreach ([$sourceLocalProfile, $opponentLocalProfile] as $localProfile) {
            $player = Server::getInstance()->getPlayerExact($localProfile->getName());
            if ($player === null || !$player->isOnline()) continue;

            $totalPlayers[] = $player;
        }

        MatchManager::getInstance()->createMatch(
            $totalPlayers,
            $kit,
            false,
            $ranked
        );
    }

    /**
     * Removes a player from the queue
     * Using their xuid and kit name
     *
     * @param LocalProfile $localProfile
     */
    public function removeQueue(LocalProfile $localProfile): void {
        unset($this->queues[$localProfile->getXuid()]);

        if (($player = Server::getInstance()->getPlayerExact($localProfile->getName())) === null) return;

        $localProfile->setMatchQueue(null);
        Practice::setProfileScoreboard($player, ProfileManager::LOBBY_SCOREBOARD);
    }

    /**
     * @param string|null $kitName
     *
     * @return int
     */
    public function getQueueCount(?string $kitName = null): int {
        $queues = $this->queues;
        if ($kitName !== null) {
            $queues = array_filter($queues, function (MatchQueue $matchQueue) use ($kitName): bool {
                return $matchQueue->getKitName() === $kitName;
            });
        }

        return count($queues);
    }

    /**
     * Looks for an opponent for a player
     * Using their xuid, kit name, and if it's ranked
     *
     * @param MatchQueue $sourceMatchQueue
     *
     * @return MatchQueue|null
     */
    public function lookupOpponent(MatchQueue $sourceMatchQueue): ?MatchQueue {
        foreach ($this->queues as $matchQueue) {
            if ($matchQueue->getXuid() === $sourceMatchQueue->getXuid()) continue;
            if (!$matchQueue->isSameType($sourceMatchQueue)) continue;

            return $matchQueue;
        }

        return null;
    }
}