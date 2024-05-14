<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\duel\Team;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;
use function ceil;
use function count;

final class TeamDuelImpl extends Duel {
    use SpectatingDuelTrait;

    /** @var Team[] */
    private array $teams = [];

    /**
     * @param Player $player
     * @param bool   $canEnd
     */
    public function removePlayer(Player $player, bool $canEnd): void {
        foreach ($this->teams as $team) {
            if (!$team->removePlayer($player->getXuid())) continue;

            break;
        }

        if (!$canEnd || count($this->getAlive()) > 1) return;

        $this->end();
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function prepare(array $totalPlayers): void {
        $teams = [];
        $teamSize = (int) ceil(count($totalPlayers) / 2);
        $teamId = 0;

        foreach ($totalPlayers as $player) {
            if (($team = $teams[$teamId] ?? null) === null) {
                $teams[$teamId] = $team = new Team($teamId, []);
            }

            $team->addPlayer($player->getXuid());

            if (count($team->getPlayers($this)) < $teamSize) continue;

            $teamId++;
        }

        $this->teams = $teams;
    }

    /**
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerPrepare(Player $player, DuelProfile $duelProfile): void {
        // TODO: Implement processPlayerPrepare() method.
    }

    /**
     * Process the player when the match ends.
     *
     * @param Player      $player
     * @param DuelProfile $duelProfile
     */
    public function processPlayerEnd(Player $player, DuelProfile $duelProfile): void {
        // TODO: Implement processPlayerEnd() method.
    }
}