<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\match\MatchStatistics;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use function count;

final class DuelProfile {

    /** @var bool */
    private bool $alive = true;

    /**
     * @param string          $xuid
     * @param string          $name
     * @param string          $matchFullName
     * @param MatchStatistics $matchStatistics
     */
    public function __construct(
        private readonly string $xuid,
        private readonly string $name,
        private readonly string $matchFullName,
        private readonly MatchStatistics $matchStatistics = new MatchStatistics()
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
     * @return MatchStatistics
     */
    public function getMatchStatistics(): MatchStatistics {
        return $this->matchStatistics;
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

    /**
     * Convert the player to spectator.
     * If the player recently joined the match, they will be added to the match.
     *
     * @param AbstractMatch $match
     * @param bool          $joined
     */
    public function convertAsSpectator(AbstractMatch $match, bool $joined): void {
        if (($player = $this->toPlayer()) === null) return;

        $this->alive = false;

        if ($joined) {
            $match->joinPlayer($player);
        } elseif (count($match->getAlive()) <= 1) {
            $match->end();
        }

        LocalProfile::resetInventory($player);

        $player->setGamemode(GameMode::SPECTATOR);
        $player->setAllowFlight(true);
        $player->setFlying(true);
    }
}