<?php

declare(strict_types=1);

namespace bitrule\practice\match\impl;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\Team;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;
use function array_merge;
use function ceil;
use function count;

final class TeamMatchImpl extends AbstractMatch {

    /** @var Team[] */
    private array $teams = [];

    /**
     * @param DuelProfile $duelProfile
     */
    public function teleportSpawn(DuelProfile $duelProfile): void {
        // TODO: Implement teleportSpawn() method.
    }

    /**
     * @param Player $player
     */
    public function removePlayer(Player $player): void {
        foreach ($this->teams as $team) {
            if (!$team->removePlayer($player->getXuid())) continue;

            break;
        }
    }

    /**
     * @return DuelProfile[]
     */
    public function getEveryone(): array {
        $players = [];

        foreach ($this->teams as $team) {
            $players = array_merge($players, $team->getPlayers());
        }

        return $players;
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function setup(array $totalPlayers): void {
        $teams = [];
        $teamSize = (int) ceil(count($totalPlayers) / 2);
        $teamId = 0;

        foreach ($totalPlayers as $player) {
            if (($team = $teams[$teamId] ?? null) === null) {
                $teams[$teamId] = $team = new Team($teamId, []);
            }

            $team->addPlayer($player->getXuid());

            if (count($team->getPlayers()) < $teamSize) continue;

            $teamId++;
        }

        $this->teams = $teams;
    }
}