<?php

declare(strict_types=1);

namespace bitrule\practice\party\impl;

use bitrule\practice\party\Member;
use bitrule\practice\party\Party;
use pocketmine\Server;

final class PartyImpl implements Party {

    /**
     * @param string $id
     * @param array<string, Member>  $members
     * @param string[]  $pendingInvites
     */
    public function __construct(
        private readonly string $id,
        private array $members = [],
        private array $pendingInvites = []
    ) {}

    /**
     * @return string
     */
    public function getId(): string {
        return $this->id;
    }

    /**
     * @return array<string, Member>
     */
    public function getMembers(): array {
        return $this->members;
    }

    /**
     * @param Member $member
     */
    public function addMember(Member $member): void {
        $this->members[$member->getXuid()] = $member;
    }

    /**
     * @param string $xuid
     */
    public function removeMember(string $xuid): void {
        unset($this->members[$xuid]);
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function isMember(string $xuid): bool {
        return isset($this->members[$xuid]);
    }

    /**
     * @param string $xuid
     */
    public function addPendingInvite(string $xuid): void {
        $this->pendingInvites[] = $xuid;
    }

    /**
     * @param string $xuid
     */
    public function removePendingInvite(string $xuid): void {
        $key = array_search($xuid, $this->pendingInvites, true);
        if ($key === false) return;

        unset($this->pendingInvites[$key]);
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function isPendingInvite(string $xuid): bool {
        return in_array($xuid, $this->pendingInvites, true);
    }

    /**
     * @param string $message
     */
    public function broadcastMessage(string $message): void {
        foreach ($this->members as $member) {
            // TODO: Change this to our own method to have better performance
            // because getPlayerExact iterates over all players
            $player = Server::getInstance()->getPlayerExact($member->getXuid());
            if ($player === null || !$player->isOnline()) continue;

            $player->sendMessage($message);
        }
    }
}