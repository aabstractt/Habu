<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use pocketmine\player\Player;
use pocketmine\Server;

final class DuelProfile {

    private bool $alive = true;

    /**
     * @param string $xuid
     * @param string $name
     * @param string $matchFullName
     */
    public function __construct(
        private readonly string $xuid,
        private readonly string $name,
        private readonly string $matchFullName
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
     * @return string
     */
    public function getMatchFullName(): string {
        return $this->matchFullName;
    }

    /**
     * @return Player|null
     */
    public function toPlayer(): ?Player {
        return Server::getInstance()->getPlayerExact($this->name);
    }

    /**
     * @return bool
     */
    public function isAlive(): bool {
        return $this->alive;
    }

    /**
     * @param bool $alive
     */
    public function setAlive(bool $alive): void {
        $this->alive = $alive;
    }

    /**
     * @param string $message
     */
    public function sendMessage(string $message): void {
        if (($player = $this->toPlayer()) === null) return;

        $player->sendMessage($message);
    }
}