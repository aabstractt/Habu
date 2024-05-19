<?php

declare(strict_types=1);

namespace bitrule\practice\party\impl;

use bitrule\practice\party\Member;
use bitrule\practice\party\Party;

final class PartyImpl implements Party {

    /**
     * @param string $id
     * @param array  $members
     */
    public function __construct(
        private readonly string $id,
        private array $members = []
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
}