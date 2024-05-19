<?php

declare(strict_types=1);

namespace bitrule\practice\party\impl;

use bitrule\practice\party\Party;
use bitrule\practice\party\PartyAdapter;
use bitrule\practice\party\Role;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;

final class DefaultPartyAdapter implements PartyAdapter {

    /** @var array<string, Party> */
    private array $parties = [];
    /** @var array<string, string> */
    private array $playersParty = [];

    /**
     * @param Player $player
     *
     * @return Party|null
     */
    public function getPartyByPlayer(Player $player): ?Party {
        $partyId = $this->playersParty[$player->getXuid()] ?? null;
        if ($partyId === null) return null;

        return $this->parties[$partyId] ?? null;
    }

    /**
     * Adapt the method to create a party.
     *
     * @param Player $source
     */
    public function createParty(Player $source): void {
        if ($this->getPartyByPlayer($source) !== null) {
            $source->sendMessage(TextFormat::RED . 'You are already in a party');

            return;
        }

        $party = new PartyImpl(Uuid::uuid4()->toString());
        $party->addMember(new MemberImpl($source->getXuid(), $source->getName(), Role::OWNER));

        $this->parties[$party->getId()] = $party;
        $this->playersParty[$source->getXuid()] = $party->getId();

        $source->sendMessage(TextFormat::GREEN . 'You have created a party');
    }

    /**
     * Adapt the method to disband a party
     *
     * @param Player $source
     */
    public function disbandParty(Player $source): void {
        $party = $this->getPartyByPlayer($source);
        if ($party === null) {
            $source->sendMessage(TextFormat::RED . 'You are not in a party');

            return;
        }

        foreach ($party->getMembers() as $member) {
            unset($this->playersParty[$member->getXuid()]);

            // TODO: Change this to our own method to have better performance
            // because getPlayerExact iterates over all players
            $player = Server::getInstance()->getPlayerExact($member->getXuid());
            if ($player === null || !$player->isOnline()) continue;

            $player->sendMessage(TextFormat::RED . 'Your party has been disbanded');
        }

        unset($this->parties[$party->getId()]);
    }

    /**
     * @param Player $source
     */
    public function onPlayerQuit(Player $source): void {
        $this->disbandParty($source);
    }
}