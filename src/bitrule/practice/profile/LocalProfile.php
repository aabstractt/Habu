<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\duel\queue\Queue;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\Practice;
use bitrule\practice\profile\scoreboard\Scoreboard;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

final class LocalProfile {

    /** @var AbstractArenaSetup|null */
    private ?AbstractArenaSetup $arenaSetup = null;
    /** @var Scoreboard|null */
    private ?Scoreboard $scoreboard = null;
    /** @var Queue|null */
    private ?Queue $queue = null;

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
     * @return Queue|null
     */
    public function getQueue(): ?Queue {
        return $this->queue;
    }

    /**
     * @param Queue|null $queue
     */
    public function setQueue(?Queue $queue): void {
        $this->queue = $queue;
    }

    /**
     * @param Player $player
     * @param bool   $showScoreboard
     */
    public function joinLobby(Player $player, bool $showScoreboard): void {
        self::setDefaultAttributes($player);
        // TODO: Give lobby items

        if (!$showScoreboard) return;

        Practice::setProfileScoreboard($player, ProfileManager::LOBBY_SCOREBOARD);
    }

    public static function setDefaultAttributes(Player $player): void {
        self::resetInventory($player);

        $player->setGamemode(GameMode::SURVIVAL);
        $player->setNoClientPredictions(false);

        $player->setHealth($player->getMaxHealth());
        $player->getHungerManager()->setFood(20);
        $player->getHungerManager()->setSaturation(20);

        $player->getXpManager()->setXpAndProgress(0, 0);
    }

    /**
     * Resets the player's inventory.
     * @param Player $player
     */
    public static function resetInventory(Player $player): void {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->getCursorInventory()->clearAll();
        $player->getOffHandInventory()->clearAll();
    }
}