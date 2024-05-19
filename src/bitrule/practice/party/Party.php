<?php

declare(strict_types=1);

namespace bitrule\practice\party;

interface Party {

    /**
     * @return string
     */
    public function getId(): string;

    /**
     * @return array<string, Member>
     */
    public function getMembers(): array;

    /**
     * @param Member $member
     */
    public function addMember(Member $member): void;

    /**
     * @param string $xuid
     */
    public function removeMember(string $xuid): void;

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function isMember(string $xuid): bool;
}