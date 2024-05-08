<?php

declare(strict_types=1);

namespace bitrule\practice\event\player;

use bitrule\practice\kit\Kit;
use pocketmine\event\Event;
use pocketmine\player\Player;

final class PlayerKitAppliedEvent extends Event {

    /**
     * @param Player $player
     * @param Kit    $kit
     */
    public function __construct(
        private readonly Player $player,
        private readonly Kit $kit
    ) {}

    /**
     * @return Player
     */
    public function getPlayer(): Player {
        return $this->player;
    }

    /**
     * @return Kit
     */
    public function getKit(): Kit {
        return $this->kit;
    }
}