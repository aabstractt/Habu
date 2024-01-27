<?php

declare(strict_types=1);

namespace bitrule\practice\match;

final class MatchRounds {

    /**
     * @param int   $currentRound
     * @param int   $maxRounds
     * @param array<string, int> $players
     */
    public function __construct(
        private int $currentRound = 0,
        private int $maxRounds = 0,
        private array $players = []
    ) {}

    /**
     * @return int
     */
    public function getCurrentRound(): int {
        return $this->currentRound;
    }

    /**
     * @param int $currentRound
     */
    public function setCurrentRound(int $currentRound): void {
        $this->currentRound = $currentRound;
    }

    /**
     * @return int
     */
    public function getMaxRounds(): int {
        return $this->maxRounds;
    }

    /**
     * @param int $maxRounds
     */
    public function setMaxRounds(int $maxRounds): void {
        $this->maxRounds = $maxRounds;
    }

    /**
     * @return array<string, int>
     */
    public function getPlayers(): array {
        return $this->players;
    }

    /**
     * @param string $xuid
     */
    public function setWinner(string $xuid): void {
        $this->players[$xuid] = ($this->players[$xuid] ?? 0) + 1;
    }

    /**
     * @return string|null
     */
    public function getWinner(): ?string {
        $winner = null;
        $winners = 0;

        foreach ($this->players as $xuid => $wins) {
            if ($this->maxRounds > $wins) continue;

            $winner = $xuid;
            $winners++;
        }

        return $winners === 1 ? $winner : null;
    }
}