<?php

declare(strict_types=1);

namespace bitrule\practice\party\impl;

use bitrule\practice\party\Member;
use bitrule\practice\party\Role;
use InvalidArgumentException;

final class MemberImpl implements Member {

    /**
     * @param string $xuid
     * @param string $name
     * @param Role   $role
     */
    public function __construct(
        private readonly string $xuid,
        private readonly string $name,
        private readonly Role $role
    ) {}

    /**
     * @return string
     */
    public function getXuid(): string {
        return $this->xuid;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return Role
     */
    public function getRole(): Role {
        return $this->role;
    }

    /**
     * Wrap the member from array data
     * This is used when other adapters are used to create a member
     *
     * @param array $data
     *
     * @return Member
     */
    public static function fromArray(array $data): Member {
        if (!isset($data['xuid'])) {
            throw new InvalidArgumentException('xuid is required');
        }

        if (!isset($data['known_name'])) {
            throw new InvalidArgumentException('known_name is required');
        }

        if (!isset($data['role'])) {
            throw new InvalidArgumentException('role is required');
        }

        return new MemberImpl(
            $data['xuid'],
            $data['known_name'],
            Role::valueOf($data['role'])
        );

    }
}