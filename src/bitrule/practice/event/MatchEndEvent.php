<?php

declare(strict_types=1);

namespace bitrule\practice\event;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\profile\DuelProfile;
use pocketmine\event\Event;

final class MatchEndEvent extends Event {

    /**
     * @param AbstractMatch $match
     * @param array         $players
     * @param array         $spectators
     * @param bool          $someoneDisconnected
     */
    public function __construct(
        private readonly AbstractMatch $match,
        private readonly ?DuelProfile $winner,
        private readonly array $players,
        private readonly array $spectators,
        private readonly bool $someoneDisconnected
    ) {}

    /**
     * @return AbstractMatch
     */
    public function getMatch(): AbstractMatch {
        return $this->match;
    }

    /**
     * @return DuelProfile|null
     */
    public function getWinner(): ?DuelProfile {
        return $this->winner;
    }

    /**
     * @return DuelProfile[]
     */
    public function getPlayers(): array {
        return $this->players;
    }

    /**
     * @return DuelProfile[]
     */
    public function getSpectators(): array {
        return $this->spectators;
    }

    /**
     * @return bool
     */
    public function hasSomeoneDisconnected(): bool {
        return $this->someoneDisconnected;
    }
}