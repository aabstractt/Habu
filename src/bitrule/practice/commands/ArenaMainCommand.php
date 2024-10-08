<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use abstractplugin\command\BaseCommand;
use bitrule\practice\commands\arena\ArenaCancelArgument;
use bitrule\practice\commands\arena\ArenaCornerArgument;
use bitrule\practice\commands\arena\ArenaCreateArgument;
use bitrule\practice\commands\arena\ArenaEditArgument;
use bitrule\practice\commands\arena\ArenaSaveArgument;
use bitrule\practice\commands\arena\ArenaYawArgument;
use bitrule\practice\commands\arena\KitCreateArgument;
use bitrule\practice\commands\arena\KitKnockbackArgument;

final class ArenaMainCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('arena', 'Arena management for Habu', '/arena hep');

        $this->setPermission('arena.command');

        $this->registerParent(
            new ArenaCreateArgument('create', 'arena.command.create'),
            new ArenaSaveArgument('save', 'arena.command.save'),
            new ArenaYawArgument('yaw', 'arena.command.yaw'),
            new ArenaCornerArgument('corner', 'arena.command.corner'),
            new ArenaEditArgument('edit', 'arena.command.edit'),
            new KitCreateArgument('createkit', 'arena.command.createkit'),
            new KitCreateArgument('toggleparty', 'arena.command.toggleparty'),
            new KitKnockbackArgument('knockback', 'arena.command.knockback'),
            new ArenaCancelArgument('cancel', 'arena.command.save')
        );
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return null;
    }
}