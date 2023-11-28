<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use bitrule\practice\manager\PlayerManager;
use bitrule\practice\player\DuelPlayer;

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
     * @return DuelPlayer[]
     */
    public function getPlayers(): array {
        return array_filter(
            array_map(
                fn (string $xuid) => PlayerManager::getInstance()->getDuelPlayer($xuid),
                $this->players
            ),
            fn (?DuelPlayer $duelPlayer) => $duelPlayer !== null
        );
    }
}