<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use abstractplugin\command\BaseCommand;
use bitrule\practice\commands\knockback\KnockbackCreateCommand;
use bitrule\practice\commands\knockback\KnockbackHighestLimitCommand;
use bitrule\practice\commands\knockback\KnockbackHitDelayCommand;
use bitrule\practice\commands\knockback\KnockbackHorizontalCommand;
use bitrule\practice\commands\knockback\KnockbackInfoCommand;
use bitrule\practice\commands\knockback\KnockbackVerticalCommand;

final class KnockbackProfileCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('kbp', 'Knockback management for Habu', '/arena hep');

        $this->setPermission('kb.command');

        $this->registerParent(
            new KnockbackCreateCommand('create', 'kb.command.create'),
            new KnockbackHorizontalCommand('horizontal', 'kb.command.horizontal'),
            new KnockbackVerticalCommand('vertical', 'kb.command.vertical'),
            new KnockbackHighestLimitCommand('highestlimit', 'kb.command.highestlimit'),
            new KnockbackHitDelayCommand('hitdelay', 'kb.command.hitdelay'),
            new KnockbackInfoCommand('info', 'kb.command.info')
        );
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return null;
    }
}