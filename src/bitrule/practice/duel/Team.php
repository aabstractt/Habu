<?php

declare(strict_types=1);

namespace bitrule\practice\duel;

use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\profile\DuelProfile;
use function array_filter;
use function array_map;
use function array_search;
use function in_array;

final class Team {

    /**
     * @param int   $id
     * @param string[] $players
     */
    public function __construct(
        private readonly int $id,
        private array $players
    ) {}

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @param string $xuid
     */
    public function addPlayer(string $xuid): void {
        $this->players[] = $xuid;
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function removePlayer(string $xuid): bool {
        if (!in_array($xuid, $this->players, true)) {
            return false;
        }

        unset($this->players[array_search($xuid, $this->players, true)]);

        return true;
    }

    /**
     * @return DuelProfile[]
     */
    public function getPlayers(): array {
        return array_filter(
            array_map(
                fn (string $xuid) => ProfileRegistry::getInstance()->getDuelProfile($xuid),
                $this->players
            ),
            fn (?DuelProfile $duelProfile) => $duelProfile !== null
        );
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function isMember(string $xuid): bool {
        return in_array($xuid, $this->players, true);
    }
}