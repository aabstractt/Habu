<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Team;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;
use function array_merge;
use function ceil;
use function count;

final class TeamDuelImpl extends AbstractMatch {

    /** @var Team[] */
    private array $teams = [];

    /**
     * @param Player $player
     */
    public function joinSpectator(Player $player): void {
        if (($team = $this->teams[2] ?? null) === null) {
            $this->teams[2] = $team = new Team(2, []);
        }

        $team->addPlayer($player->getXuid());

        $this->postJoinSpectator($player);
    }

    /**
     * Get the spawn id of the player
     * If is single match the spawn id is the index of the player in the players array.
     * If is team match the spawn id is the team id of the player.
     *
     * @param string $xuid
     *
     * @return int
     */
    public function getSpawnId(string $xuid): int {
        foreach ($this->teams as $team) {
            if (!$team->isMember($xuid)) continue;

            return $team->getId();
        }

        return -1;
    }

    /**
     * @param Player $player
     * @param bool   $canEnd
     */
    public function removePlayer(Player $player, bool $canEnd): void {
        if ($canEnd && count($this->getAlive()) <= 1) {
            $this->end();
        }

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
    public function prepare(array $totalPlayers): void {
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

    /**
     * This method is called when the match stage change to Ending.
     * Usually is used to send the match results to the players.
     */
    public function end(): void {
        throw new \RuntimeException('Not implemented');
    }
    /**
     * @param string $xuid
     *
     * @return string|null
     */
    public function getOpponentName(string $xuid): ?string {
        throw new \RuntimeException('Not implemented');
    }
}