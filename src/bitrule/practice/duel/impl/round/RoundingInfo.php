<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\round;

final class RoundingInfo {

    /**
     * @param int   $round
     * @param int   $maxRounds
     * @param array<string, int> $players
     */
    public function __construct(
        private int          $round = 0,
        private readonly int $maxRounds = 0,
        private array $players = [],
        private array $worlds = []
    ) {}

    /**
     * @return int
     */
    public function getRound(): int {
        return $this->round;
    }

    /**
     * @param int $round
     */
    public function setRound(int $round): void {
        $this->round = $round;
    }

    /**
     * @return int
     */
    public function getMaxRounds(): int {
        return $this->maxRounds;
    }

    /**
     * Increase the win count of a player.
     *
     * @param string $sourceXuid
     */
    public function increaseWin(string $sourceXuid): void {
        $this->players[$sourceXuid] = ($this->players[$sourceXuid] ?? 0) + 1;
    }

    /**
     * @return string|null
     */
    public function findWinner(): ?string {
        $winnerXuid = null;

        foreach ($this->players as $playerXuid => $wins) {
            if ($this->maxRounds > $wins) continue;

            if ($winnerXuid !== null) return null;

            $winnerXuid = $playerXuid;
        }

        return is_numeric($winnerXuid) ? strval($winnerXuid) : $winnerXuid;
    }

    /**
     * @param string $worldName
     */
    public function registerWorld(string $worldName): void {
        $this->worlds[] = $worldName;
    }

    /**
     * @return string[]
     */
    public function getWorlds(): array {
        return $this->worlds;
    }
}