<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use abstractplugin\command\BaseCommand;
use bitrule\practice\commands\events\EventsStartArgument;

final class EventsMainCommand extends BaseCommand {

    public function __construct() {
        parent::__construct('events', 'Event management', '/events hep');

        $this->setPermission($this->getPermission());

        $this->registerParent(new EventsStartArgument('start', $this->getPermission() . '.start'));
    }

    /**
     * @return string|null
     */
    public function getPermission(): ?string {
        return 'practice.command.events';
    }
}