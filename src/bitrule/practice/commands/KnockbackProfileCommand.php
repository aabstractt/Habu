<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use abstractplugin\command\BaseCommand;
use bitrule\practice\commands\knockback\KnockbackCreateCommand;
use bitrule\practice\commands\knockback\KnockbackHighestLimitCommand;
use bitrule\practice\commands\knockback\KnockbackHorizontalCommand;
use bitrule\practice\commands\knockback\KnockbackVerticalCommand;
use pocketmine\utils\TextFormat;

final class KnockbackProfileCommand extends BaseCommand {

    public const PREFIX = TextFormat::BLUE . TextFormat::BOLD . 'Knockback' . TextFormat::DARK_GRAY . '> ' . TextFormat::RESET;

    public function __construct() {
        parent::__construct('kbp', 'Knockback management for Practice', '/arena hep');

        $this->setPermission('kb.command');

        $this->registerParent(
            new KnockbackCreateCommand('create', 'kb.command.create'),
            new KnockbackHorizontalCommand('horizontal', 'kb.command.horizontal'),
            new KnockbackVerticalCommand('vertical', 'kb.command.vertical'),
            new KnockbackHighestLimitCommand('highestlimit', 'kb.command.highestlimit')
        );
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return null;
    }
}