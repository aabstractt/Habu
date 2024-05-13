<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;

final class PartySplitDuelImpl extends Duel {
    use SpectatingDuelTrait;

    /**
     * Process the player when they are preparing for the match.
     *
     * @param Player      $player      The player who is preparing for the match.
     * @param DuelProfile $duelProfile The profile of the duel that the player is preparing for.
     */
    public function processPlayerPrepare(Player $player, DuelProfile $duelProfile): void {
        // TODO: Implement processPlayerPrepare() method.
    }

    /**
     * This method is called when the match stage changes to Ending.
     * Usually, it is used to send the match results to the players.
     */
    public function end(): void {
        // TODO: Implement end() method.
    }

    /**
     * Process the player when the match ends.
     *
     * @param Player      $player      The player who has finished the match.
     * @param DuelProfile $duelProfile The profile of the duel that the player has finished.
     */
    public function processPlayerEnd(Player $player, DuelProfile $duelProfile): void {
        // TODO: Implement processPlayerEnd() method.
    }

    /**
     * Remove a player from the match.
     * Check if the match can end.
     * Usually, this is checked when the player dies or leaves the match.
     *
     * @param Player $player The player to be removed from the match.
     * @param bool   $canEnd A flag indicating whether the match can end after the player is removed.
     */
    public function removePlayer(Player $player, bool $canEnd): void {
        // TODO: Implement removePlayer() method.
    }
}