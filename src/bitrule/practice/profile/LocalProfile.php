<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\duel\queue\Queue;
use bitrule\practice\Practice;
use bitrule\practice\profile\scoreboard\Scoreboard;
use bitrule\practice\registry\ProfileRegistry;
use InvalidArgumentException;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use function explode;
use function is_string;

final class LocalProfile {

    /** @var AbstractArenaSetup|null */
    private ?AbstractArenaSetup $arenaSetup = null;
    /** @var Scoreboard|null */
    private ?Scoreboard $scoreboard = null;
    /** @var Queue|null */
    private ?Queue $queue = null;
    /** @var string The knockback profile of the player. */
    private string $knockbackProfile = 'default';

    /** @var bool Whether the player's knockback motion is the initial motion. */
    public bool $initialKnockbackMotion = false;
    /** @var bool Whether the player's knockback motion should be cancelled. */
    public bool $cancelKnockbackMotion = false;

    /**
     * @param string $xuid
     * @param string $name
     * @param int    $elo
     */
    public function __construct(
        private readonly string $xuid,
        private readonly string $name,
        private int $elo
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
     * @param int $elo
     */
    public function setElo(int $elo): void {
        $this->elo = $elo;
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
        $this->setKnockbackProfile('default');
        // TODO: Give lobby items

        if (!$showScoreboard) return;

        Practice::setProfileScoreboard($player, ProfileRegistry::LOBBY_SCOREBOARD);
    }

    /**
     * @param string $knockbackProfile
     */
    public function setKnockbackProfile(string $knockbackProfile): void {
        $this->knockbackProfile = $knockbackProfile;
    }

    /**
     * @return string
     */
    public function getKnockbackProfile(): string {
        return $this->knockbackProfile;
    }

    public static function setDefaultAttributes(Player $player): void {
        self::resetInventory($player);

        $player->setHealth($player->getMaxHealth());
        $player->getHungerManager()->setFood(20);
        $player->getHungerManager()->setSaturation(20);

        $player->getXpManager()->setXpAndProgress(0, 0);

        $player->setGamemode(GameMode::ADVENTURE);

        /** @var array<int, array<int, string|Item>> $items */
        $items = [
        	0 => ['competitive-duel', VanillaItems::DIAMOND_SWORD()],
        	1 => ['unranked-duel', VanillaItems::GOLDEN_SWORD()],
        	4 => ['spectate', VanillaItems::CLOCK()],
        	7 => ['parties', VanillaItems::PAPER()],
        	8 => ['settings', VanillaItems::COMPASS()]
        ];

        foreach ($items as $inventorySlot => [$itemType, $item]) {
            if (!is_string($itemType)) {
                throw new InvalidArgumentException('Item type must be a string');
            }

            if (!$item instanceof Item) {
                throw new InvalidArgumentException('Item must be an instance of Item');
            }

            $item->setCustomName(Practice::wrapMessage('items.' . $itemType . '.custom-name'));
            $item->setLore(explode("\n", Practice::wrapMessage('items.' . $itemType . '.lore')));

            $nbt = $item->getNamedTag();

            $nbt->setString('ItemType', $itemType);
            $item->setNamedTag($nbt);

            $player->getInventory()->setItem($inventorySlot, $item);
        }
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

        $player->setGamemode(GameMode::SURVIVAL);
        $player->setNoClientPredictions(false);

        $player->setAllowFlight(false);
        $player->setFlying(false);

        $player->getEffects()->clear();
    }
}