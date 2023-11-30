<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use abstractplugin\command\BaseCommand;
use bitrule\practice\commands\arena\ArenaCreateArgument;
use bitrule\practice\commands\arena\ArenaSaveArgument;

final class ArenaMainCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('arena', 'Arena management for Practice', '/arena hep');

        $this->registerParent(
            new ArenaCreateArgument('create', 'arena.command.create'),
            new ArenaSaveArgument('save', 'arena.command.save')
        );
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return null;
    }
}