<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\duel\queue\Queue;
use bitrule\practice\Practice;
use bitrule\practice\profile\LocalProfile;
use Exception;
use pocketmine\player\Player;
use pocketmine\promise\Promise;
use pocketmine\promise\PromiseResolver;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function time;

final class QueueRegistry {

    use SingletonTrait;

    /** @var array<string, Queue> */
    private array $queues = [];

    /**
     * Creates a queue for a player
     * Using their xuid, kit name, and if it's ranked
     * Also checks if there is an opponent in the queue
     * If there is, it will remove both players from the queue
     *
     * @param LocalProfile $sourceLocalProfile
     * @param string       $kitName
     * @param bool         $ranked
     *
     * @return Promise<Queue>
     */
    public function createQueue(LocalProfile $sourceLocalProfile, string $kitName, bool $ranked): Promise {
        $promiseResolver = new PromiseResolver();

        if (($kit = KitRegistry::getInstance()->getKit($kitName)) === null) {
            $promiseResolver->reject();

            return $promiseResolver->getPromise();
        }

        $this->queues[$sourceXuid = $sourceLocalProfile->getXuid()] = $queue = new Queue($sourceXuid, $kitName, $ranked, time());

        $opponentMatchQueue = $this->lookupOpponent($queue);
        if ($opponentMatchQueue === null) {
            $promiseResolver->resolve($queue);

            return $promiseResolver->getPromise();
        }

        $this->removeQueue($sourceLocalProfile);

        if (($opponentLocalProfile = ProfileRegistry::getInstance()->getLocalProfile($opponentMatchQueue->getXuid())) === null) {
            throw new RuntimeException('Opponent profile no exists.');
        }

        $this->removeQueue($opponentLocalProfile);

        /** @var Player[] $totalPlayers */
        $totalPlayers = [];
        foreach ([$sourceLocalProfile, $opponentLocalProfile] as $localProfile) {
            $player = Server::getInstance()->getPlayerExact($localProfile->getName());
            if ($player === null || !$player->isOnline()) continue;

            $totalPlayers[] = $player;
        }

        $promiseResolver->resolve($queue);

        try {
            DuelRegistry::getInstance()->createDuel(
                $totalPlayers,
                [],
                $kit,
                $ranked
//            new RoundingInfo(
//                0,
//                3,
//                [],
//                []
//            )
            );
        } catch (Exception $e) {
            foreach ($totalPlayers as $player) {
                $player->sendMessage(TextFormat::RED . 'Something went wrong while creating the duel.');
                $player->sendMessage(TextFormat::RED . $e->getMessage());
            }
        }

        return $promiseResolver->getPromise();
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

        $localProfile->setQueue(null);
        Practice::setProfileScoreboard($player, ProfileRegistry::LOBBY_SCOREBOARD);
    }

    /**
     * @param string|null $kitName
     *
     * @return int
     */
    public function getQueueCount(?string $kitName = null): int {
        $queueCount = 0;

        foreach ($this->queues as $queue) {
            if ($kitName !== null && $queue->getKitName() !== $kitName) continue;

            $queueCount++;
        }

        return $queueCount;
    }

    /**
     * Looks for an opponent for a player
     * Using their xuid, kit name, and if it's ranked
     *
     * @param Queue $sourceMatchQueue
     *
     * @return Queue|null
     */
    public function lookupOpponent(Queue $sourceMatchQueue): ?Queue {
        foreach ($this->queues as $matchQueue) {
            if ($matchQueue->getXuid() === $sourceMatchQueue->getXuid()) continue;
            if (!$matchQueue->isSameType($sourceMatchQueue)) continue;

            return $matchQueue;
        }

        return null;
    }
}