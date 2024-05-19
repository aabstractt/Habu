<?php

declare(strict_types=1);

namespace bitrule\practice\party;

use pocketmine\player\Player;

interface PartyAdapter {

    /**
     * @param Player $player
     *
     * @return Party|null
     */
    public function getPartyByPlayer(Player $player): ?Party;

    /**
     * Adapt the method to create a party.
     *
     * @param Player $source
     */
    public function createParty(Player $source): void;

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     */
    public function disbandParty(Player $source): void;

    /**
     * @param Player $source
     */
    public function onPlayerQuit(Player $source): void;
}