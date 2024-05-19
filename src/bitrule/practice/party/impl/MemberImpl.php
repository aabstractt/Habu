<?php

declare(strict_types=1);

namespace bitrule\practice\party\impl;

use bitrule\practice\party\Member;
use bitrule\practice\party\Role;

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
}