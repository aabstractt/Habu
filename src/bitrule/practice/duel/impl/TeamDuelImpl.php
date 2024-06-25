<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelMember;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\duel\Team;
use bitrule\practice\Habu;
use bitrule\practice\TranslationKey;
use bitrule\scoreboard\ScoreboardRegistry;
use pocketmine\player\Player;
use RuntimeException;
use function ceil;
use function count;

final class TeamDuelImpl extends Duel {
    use SpectatingDuelTrait;

    /**
     * @param Player $player
     */
    public function removePlayer(Player $player): void {
        if ($this->ending) return;

        $duelMember = $this->getMember($player->getXuid());
        if ($duelMember === null) {
            throw new RuntimeException('Player not found in the match.');
        }

        if ($duelMember->isAlive()) {
            $duelMember->convertAsSpectator($this, false);
        }

        if (count($this->getAlive()) > 1) return;

        $this->end();
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function prepare(array $totalPlayers): void {
        $teamSize = (int) ceil(count($totalPlayers) / 2);
        $teamId = 0;

        foreach ($totalPlayers as $player) {
            if (count(array_filter($this->playersSpawn, fn(int $id) => $id === $teamId)) >= $teamSize) {
                $teamId++;
            }

            $this->playersSpawn[$player->getXuid()] = $teamId;
        }
    }

    /**
     * @param Player     $player
     * @param DuelMember $duelMember
     */
    public function processPlayerPrepare(Player $player, DuelMember $duelMember): void {
        ScoreboardRegistry::getInstance()->apply($player, Habu::MATCH_STARTING_SCOREBOARD);
    }

    /**
     * Process the player when the match ends.
     *
     * @param Player     $player
     * @param DuelMember $duelMember
     */
    public function processPlayerEnd(Player $player, DuelMember $duelMember): void {
        // TODO: Implement processPlayerEnd() method.
    }
}