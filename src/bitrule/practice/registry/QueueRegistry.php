<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\NormalDuelImpl;
use bitrule\practice\duel\queue\Queue;
use bitrule\practice\Habu;
use bitrule\practice\profile\Profile;
use Exception;
use pocketmine\player\Player;
use pocketmine\promise\PromiseResolver;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;
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
     * @param Profile $sourceProfile
     * @param string  $kitName
     * @param bool    $ranked
     *
     * @return Queue|null
     */
    public function createQueue(Profile $sourceProfile, string $kitName, bool $ranked): ?Queue {
        if (($kit = KitRegistry::getInstance()->getKit($kitName)) === null) {
            throw new RuntimeException('Kit not found.');
        }

        $this->queues[$sourceXuid = $sourceProfile->getXuid()] = $queue = new Queue($sourceXuid, $kitName, $ranked, time());

        $opponentMatchQueue = $this->lookupOpponent($queue);
        if ($opponentMatchQueue === null) return $queue;

        if (($opponentProfile = ProfileRegistry::getInstance()->getProfile($opponentMatchQueue->getXuid())) === null) {
            throw new RuntimeException('Opponent profile no exists.');
        }

        /** @var Player[] $totalPlayers */
        $totalPlayers = [];
        foreach ([$sourceProfile, $opponentProfile] as $profile) {
            $player = Server::getInstance()->getPlayerExact($profile->getName());
            if ($player === null || !$player->isOnline()) continue;

            $totalPlayers[] = $player;

            $this->removeQueue($profile);
        }

        try {
            $arenaProperties = ArenaRegistry::getInstance()->getRandomArena($kit);
            if ($arenaProperties === null) {
                throw new RuntimeException('No arenas available for duel type: ' . $kit->getName());
            }

            DuelRegistry::getInstance()->prepareDuel(
                $totalPlayers,
                new NormalDuelImpl(
                    $arenaProperties,
                    $kit,
                    Uuid::uuid4()->toString(),
                    $ranked
                )
            );
        } catch (Exception $e) {
            foreach ($totalPlayers as $player) {
                $player->sendMessage(TextFormat::RED . 'Something went wrong while creating the duel.');
                $player->sendMessage(TextFormat::RED . $e->getMessage());
            }
        }

        return null;
    }

    /**
     * Removes a player from the queue
     * Using their xuid and kit name
     *
     * @param Profile $profile
     */
    public function removeQueue(Profile $profile): void {
        unset($this->queues[$profile->getXuid()]);

        if (($player = Server::getInstance()->getPlayerExact($profile->getName())) === null) return;

        $profile->setQueue(null);
        Habu::applyScoreboard($player, ProfileRegistry::LOBBY_SCOREBOARD);
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