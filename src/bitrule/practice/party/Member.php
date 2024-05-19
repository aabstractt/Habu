<?php

declare(strict_types=1);

namespace bitrule\practice\party;

interface Member {

    /**
     * @return string
     */
    public function getXuid(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return Role
     */
    public function getRole(): Role;
}