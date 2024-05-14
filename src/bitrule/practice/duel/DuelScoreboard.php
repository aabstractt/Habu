<?php

declare(strict_types=1);

namespace bitrule\practice\duel;

use bitrule\practice\profile\LocalProfile;
use pocketmine\player\Player;

interface DuelScoreboard {

    /**
     * This is the scoreboard identifier of the arena.
     *
     * @return string
     */
    public function getScoreboardId(): string;

    /**
     * Replace placeholders in the text.
     *
     * @param Duel         $duel
     * @param Player       $source
     * @param LocalProfile $localProfile
     * @param string       $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Duel $duel, Player $source, LocalProfile $localProfile, string $identifier): ?string;
}