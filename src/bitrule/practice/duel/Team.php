<?php

declare(strict_types=1);

namespace bitrule\practice\duel;

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
        private array $players = []
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
     * @return DuelMember[]
     */
    public function getPlayers(Duel $duel): array {
        return array_filter(
            array_map(
                fn (string $xuid) => $duel->getEveryone()[$xuid] ?? null,
                $this->players
            ),
            fn (?DuelMember $duelMember) => $duelMember !== null
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