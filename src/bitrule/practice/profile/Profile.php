<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use bitrule\parties\PartiesPlugin;
use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\Habu;
use bitrule\scoreboard\ScoreboardRegistry;
use InvalidArgumentException;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\math\Vector3;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use function explode;
use function is_string;

final class Profile {

    /** @var AbstractArenaSetup|null */
    private ?AbstractArenaSetup $arenaSetup = null;
    /** @var string The knockback profile of the player. */
    private string $knockbackProfile = 'default';

    /** @var Vector3|null The motion modified by the knockback profile. */
    public ?Vector3 $motion = null;

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
     * @param Player $player
     * @param bool   $showScoreboard
     */
    public function applyDefaultAttributes(Player $player, bool $showScoreboard): void {
        self::setDefaultAttributes($player);
        $this->setKnockbackProfile('default');
        // TODO: Give lobby items

        if (!$showScoreboard) return;

        ScoreboardRegistry::getInstance()->apply($player, Habu::LOBBY_SCOREBOARD);
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

        $partyAdapter = PartiesPlugin::getInstance()->getPartyAdapter();
        if ($partyAdapter === null) {
            throw new InvalidArgumentException('Party adapter not found');
        }

        $party = $partyAdapter->getPartyByPlayer($player->getXuid());
        if ($party !== null && $party->getOwnership()->getXuid() === $player->getXuid()) {
            /** @var array<int, array<int, string|Item>> $items */
            $items = [
            	0 => ['party-split', VanillaItems::DIAMOND_SWORD()],
            	1 => ['party-ffa', VanillaItems::GOLDEN_SWORD()],
            	4 => ['parties-duel', VanillaItems::CLOCK()],
            	7 => ['parties', VanillaItems::PAPER()],
            	8 => ['settings', VanillaItems::COMPASS()]
            ];
        } else {
            /** @var array<int, array<int, string|Item>> $items */
            $items = [
            	0 => ['competitive-duel', VanillaItems::DIAMOND_SWORD()],
            	1 => ['unranked-duel', VanillaItems::GOLDEN_SWORD()],
            	4 => ['spectate', VanillaItems::CLOCK()],
            	7 => ['parties', VanillaItems::PAPER()],
            	8 => ['settings', VanillaItems::COMPASS()]
            ];
        }

        foreach ($items as $inventorySlot => [$itemType, $item]) {
            if (!is_string($itemType)) {
                throw new InvalidArgumentException('Item type must be a string');
            }

            if (!$item instanceof Item) {
                throw new InvalidArgumentException('Item must be an instance of Item');
            }

            $item->setCustomName(Habu::wrapMessage('items.' . $itemType . '.custom-name'));
            $item->setLore(explode("\n", Habu::wrapMessage('items.' . $itemType . '.lore')));

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

        $player->setHealth($player->getMaxHealth());

        $player->setGamemode(GameMode::SURVIVAL);
        $player->setNoClientPredictions(false);

        $player->setAllowFlight(false);
        $player->setFlying(false);

        $player->getEffects()->clear();
    }
}