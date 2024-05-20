<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use abstractplugin\command\BaseCommand;
use bitrule\practice\commands\party\PartyCreateArgument;
use bitrule\practice\commands\party\PartyDisbandArgument;
use bitrule\practice\commands\party\PartyInviteArgument;
use bitrule\practice\commands\party\PartyLeaveArgument;
use pocketmine\lang\Translatable;

final class PartyCommand extends BaseCommand {

    public function __construct(string $name, Translatable|string $description = "", Translatable|string|null $usageMessage = null, array $aliases = []) {
        parent::__construct($name, $description, $usageMessage, $aliases);

        $this->setPermission($this->getPermission());

        $this->registerParent(
            new PartyCreateArgument('create'),
            new PartyInviteArgument('invite'),
            new PartyLeaveArgument('leave'),
            new PartyDisbandArgument('disband'),
        );
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return 'practice.party';
    }
}