<?php

declare(strict_types=1);

namespace bitrule\practice\match;

final class MatchQueue {

    /**
     * @param string $xuid
     * @param string $kitName
     * @param bool   $ranked
     * @param int    $timestamp
     */
    public function __construct(
        private readonly string $xuid,
        private readonly string $kitName,
        private readonly bool $ranked,
        private readonly int $timestamp
    ) {}

    /**
     * @return string
     */
    public function getXuid(): string {
        return $this->xuid;
    }

    /**
     * @return string
     */
    public function getKitName(): string {
        return $this->kitName;
    }

    /**
     * @return bool
     */
    public function isRanked(): bool {
        return $this->ranked;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int {
        return $this->timestamp;
    }

    /**
     * Checks if the given MatchQueue is the same type as this one.
     * This means that the kit and ranked status are the same.
     * This is used to determine if a player can be matched with another player.
     * For example, a player in a ranked queue cannot be matched with a player in an unranked queue.
     * This is because the player in the ranked queue will lose/gain points while the player in the unranked queue will not.
     *
     * @param MatchQueue $matchQueue
     *
     * @return bool
     */
    public function isSameType(self $matchQueue): bool {
        return $this->ranked === $matchQueue->ranked && $this->kitName === $matchQueue->kitName;
    }
}