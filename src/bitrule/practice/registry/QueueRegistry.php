<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\duel\impl\NormalDuelImpl;
use bitrule\practice\duel\queue\Queue;
use Exception;
use pocketmine\player\Player;
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
     * @param string $sourceXuid
     * @param string $kitName
     * @param bool   $ranked
     *
     * @return Queue|null
     */
    public function createQueue(string $sourceXuid, string $kitName, bool $ranked): ?Queue {
        if (($kit = KitRegistry::getInstance()->getKit($kitName)) === null) {
            throw new RuntimeException('Kit not found.');
        }

        $this->queues[$sourceXuid] = $queue = new Queue($sourceXuid, $kitName, $ranked, time());

        $opponentMatchQueue = $this->lookupOpponent($queue);
        if ($opponentMatchQueue === null) return $queue;

        /** @var Player[] $totalPlayers */
        $totalPlayers = [];
        foreach ([$sourceXuid, $opponentMatchQueue->getXuid()] as $xuid) {
            $player = DuelRegistry::getInstance()->getPlayerObject($xuid);
            if ($player === null || !$player->isOnline()) continue;

            $totalPlayers[] = $player;

            $this->removeQueue($player);
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
     * @param Player $player
     */
    public function removeQueue(Player $player): void {
        unset($this->queues[$player->getXuid()]);
    }

    /**
     * @param Player $player
     *
     * @return Queue|null
     */
    public function getQueueByPlayer(Player $player): ?Queue {
        return $this->queues[$player->getXuid()] ?? null;
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