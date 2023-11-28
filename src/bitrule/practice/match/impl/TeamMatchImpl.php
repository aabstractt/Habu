<?php

declare(strict_types=1);

namespace bitrule\practice\match\impl;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\Team;
use bitrule\practice\player\DuelPlayer;
use pocketmine\player\Player;

final class TeamMatchImpl extends AbstractMatch {

    /** @var Team[] */
    private array $teams = [];

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
     * @return DuelPlayer[]
     */
    public function getEveryone(): array {
        $players = [];

        foreach ($this->teams as $team) {
            $players = array_merge($players, $team->getPlayers());
        }

        return $players;
    }

    /**
     * @param TeamMatchImpl $match
     * @param array         $totalPlayers
     */
    public static function setup(TeamMatchImpl $match, array $totalPlayers): void {
        $teams = [];
        $teamSize = (int) ceil(count($totalPlayers) / 2);
        $teamId = 0;

        foreach ($totalPlayers as $player) {
            if (($team = $teams[$teamId] ?? null) === null) {
                $teams[$teamId] = $team = new Team($teamId, []);
            }

            $team->addPlayer($player);

            if (count($teams[$teamId]->getPlayers()) < $teamSize) continue;

            $teamId++;
        }

        $match->teams = $teams;
    }
}