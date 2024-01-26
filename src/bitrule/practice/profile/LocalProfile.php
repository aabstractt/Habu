<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\match\MatchQueue;
use bitrule\practice\profile\scoreboard\Scoreboard;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class LocalProfile {

    /** @var AbstractArenaSetup|null */
    private ?AbstractArenaSetup $arenaSetup = null;
    /** @var Scoreboard|null */
    private ?Scoreboard $scoreboard = null;
    /** @var MatchQueue|null */
    private ?MatchQueue $matchQueue = null;

    public function __construct(
        private readonly string $xuid,
        private readonly string $name
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
     * @return AbstractArenaSetup|null
     */
    public function getArenaSetup(): ?AbstractArenaSetup {
        return $this->arenaSetup;
    }

    /**
     * @param AbstractArenaSetup|null $arenaSetup
     */
    public function setArenaSetup(?AbstractArenaSetup $arenaSetup): void {
        $this->arenaSetup = $arenaSetup;
    }

    /**
     * @return Scoreboard|null
     */
    public function getScoreboard(): ?Scoreboard {
        return $this->scoreboard;
    }

    /**
     * @param Scoreboard|null $scoreboard
     */
    public function setScoreboard(?Scoreboard $scoreboard): void {
        $this->scoreboard = $scoreboard;
    }

    /**
     * @return MatchQueue|null
     */
    public function getMatchQueue(): ?MatchQueue {
        return $this->matchQueue;
    }

    /**
     * @param MatchQueue|null $matchQueue
     */
    public function setMatchQueue(?MatchQueue $matchQueue): void {
        $this->matchQueue = $matchQueue;
    }
}