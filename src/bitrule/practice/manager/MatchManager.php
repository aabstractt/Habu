<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\kit\Kit;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\impl\SingleMatchImpl;
use bitrule\practice\match\impl\TeamMatchImpl;
use bitrule\practice\match\MatchQueue;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\profile\scoreboard\Scoreboard;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class MatchManager {
    use SingletonTrait;

    /** @var array<string, AbstractMatch> */
    private array $matches = [];
    /** @var int */
    private int $matchesPlayed = 0;
    /** @var array<string, array<string, MatchQueue>> */
    private array $matchQueues = [];

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
        $match->postSetup($totalPlayers);

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

    /**
     * Creates a queue for a player
     * Using their xuid, kit name, and if it's ranked
     * Also checks if there is an opponent in the queue
     * If there is, it will remove both players from the queue
     *
     * @param string $sourceXuid
     * @param string $kitName
     * @param bool   $ranked
     */
    public function createQueue(string $sourceXuid, string $kitName, bool $ranked): void {
        if (($localProfile = ProfileManager::getInstance()->getLocalProfile($sourceXuid)) === null) {
            throw new RuntimeException('Local profile not found.');
        }

        if (!isset($this->matchQueues[$kitName])) $this->matchQueues[$kitName] = [];

        $matchQueues = &$this->matchQueues[$kitName];
        $matchQueues[$sourceXuid] = $matchQueue = new MatchQueue($sourceXuid, $kitName, $ranked, time());

        $localProfile->setMatchQueue($matchQueue);
        ProfileManager::getInstance()->setScoreboard($localProfile, ProfileManager::QUEUE_SCOREBOARD);

        $opponentMatchQueue = $this->lookupOpponent($matchQueue);
        if ($opponentMatchQueue === null) return;

        if (($kit = KitManager::getInstance()->getKit($kitName)) === null) {
            throw new RuntimeException('Kit no exists.');
        }

        $this->removeQueue($sourceXuid, $kitName);
        $this->removeQueue($opponentMatchQueue->getXuid(), $kitName);

        // TODO: Remove from the queue into my profile

        $totalPlayers = [];
        foreach ([$sourceXuid, $opponentMatchQueue->getXuid()] as $xuid) {
            $localProfile = ProfileManager::getInstance()->getLocalProfile($xuid);
            if ($localProfile === null) continue;

            $player = Server::getInstance()->getPlayerExact($localProfile->getName());
            if ($player === null) continue;

            $totalPlayers[] = $player;
        }

        $this->createMatch(
            $totalPlayers,
            $kit,
            false,
            $ranked
        );
    }

    /**
     * Removes a player from the queue
     * Using their xuid and kit name
     * @param string $xuid
     * @param string $kitName
     */
    private function removeQueue(string $xuid, string $kitName): void {
        unset($this->matchQueues[$kitName][$xuid]);
    }

    /**
     * @param string|null $kitName
     *
     * @return int
     */
    public function getQueueCount(?string $kitName): int {
        if ($kitName !== null) return count($this->matchQueues[$kitName] ?? []);

        $count = 0;
        foreach ($this->matchQueues as $matchQueue) {
            $count += count($matchQueue);
        }

        return $count;
    }

    /**
     * @param string|null $kitName
     *
     * @return int
     */
    public function getMatchCount(?string $kitName): int {
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
     * Looks for an opponent for a player
     * Using their xuid, kit name, and if it's ranked
     *
     * @param MatchQueue $sourceMatchQueue
     *
     * @return MatchQueue|null
     */
    public function lookupOpponent(MatchQueue $sourceMatchQueue): ?MatchQueue {
        $matchQueues = $this->matchQueues[$sourceMatchQueue->getKitName()] ?? [];

        foreach ($matchQueues as $matchQueue) {
            if ($matchQueue->getXuid() === $sourceMatchQueue->getXuid()) continue;
            if (!$matchQueue->isSameType($sourceMatchQueue)) continue;

            return $matchQueue;
        }

        return null;
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