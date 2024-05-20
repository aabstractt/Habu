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
     * @param Player $source
     * @param Player $target
     * @param Party  $party
     */
    public function processInvitePlayer(Player $source, Player $target, Party $party): void;

    /**
     * @param Player $source
     * @param Player $target
     * @param Party  $party
     */
    public function processKickPlayer(Player $source, Player $target, Party $party): void;

    /**
     * @param Player $source
     * @param Party  $party
     */
    public function processLeavePlayer(Player $source, Party $party): void;

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     * @param Party  $party
     */
    public function disbandParty(Player $source, Party $party): void;

    /**
     * @param Player $source
     */
    public function onPlayerQuit(Player $source): void;
}