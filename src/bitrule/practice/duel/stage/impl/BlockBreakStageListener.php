<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\duel\Duel;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\player\Player;

interface BlockBreakStageListener {

    /**
     * @param Duel            $duel
     * @param Player          $player
     * @param BlockBreakEvent $ev
     */
    public function onBlockBreakEvent(Duel $duel, Player $player, BlockBreakEvent $ev): void;
}