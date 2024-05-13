<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelStatistics;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\Server;
use function count;

final class DuelProfile {

    /** @var bool */
    private bool $alive = true;

    /**
     * @param string         $xuid
     * @param string         $name
     * @param int            $elo
     * @param bool           $playing
     * @param DuelStatistics $duelStatistics
     */
    public function __construct(
        private readonly string $xuid,
        private readonly string $name,
        private readonly int $elo,
        private readonly bool $playing,
        private readonly DuelStatistics $duelStatistics = new DuelStatistics()
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
     * @return int
     */
    public function getElo(): int {
        return $this->elo;
    }

    /**
     * @return DuelStatistics
     */
    public function getDuelStatistics(): DuelStatistics {
        return $this->duelStatistics;
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
     * @return bool
     */
    public function isPlaying(): bool {
        return $this->playing;
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
     * @param Duel $duel
     * @param bool $joined
     */
    public function convertAsSpectator(Duel $duel, bool $joined): void {
        if (($player = $this->toPlayer()) === null) return;

        $this->alive = false;

        if ($joined) {
            $duel->joinSpectator($player);
        } elseif (count($duel->getAlive()) <= 1) {
            $duel->end();
        }

        LocalProfile::resetInventory($player);

        $player->setGamemode(GameMode::SPECTATOR);
        $player->setAllowFlight(true);
        $player->setFlying(true);
    }

    /**
     * @param Player $player
     * @param int    $elo
     *
     * @return self
     */
    public static function normal(Player $player, int $elo): self {
        return new self($player->getXuid(), $player->getName(), $elo, true);
    }

    /**
     * Create a spectator profile
     *
     * @param Player $source
     *
     * @return self
     */
    public static function spectator(Player $source): self {
        return new self($source->getXuid(), $source->getName(), 0, false);
    }
}