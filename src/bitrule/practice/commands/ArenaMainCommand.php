<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use abstractplugin\command\BaseCommand;

final class ArenaMainCommand extends BaseCommand {

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return null;
    }
}