<?php

declare(strict_types=1);

namespace bitrule\practice\kit;

use bitrule\practice\event\player\PlayerKitAppliedEvent;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\item\Item;
use pocketmine\player\Player;
use pocketmine\utils\Limits;

final class Kit {

    public const BOXING = 'Boxing';
    public const SUMO = 'Sumo';

    /**
     * @param string           $name
     * @param bool             $partyPlayable
     * @param array<int, Item> $inventoryItems
     * @param array<int, Item> $armorItems
     * @param string           $knockbackProfile
     */
    public function __construct(
        private readonly string $name,
        private bool $partyPlayable,
        private array $inventoryItems,
        private array $armorItems,
        private string $knockbackProfile
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return bool
     */
    public function isPartyPlayable(): bool {
        return $this->partyPlayable;
    }

    /**
     * Set if the kit is party playable
     *
     * @param bool $partyPlayable
     */
    public function setPartyPlayable(bool $partyPlayable): void {
        $this->partyPlayable = $partyPlayable;
    }

    /**
     * @return array<int, Item>
     */
    public function getInventoryItems(): array {
        return $this->inventoryItems;
    }

    /**
     * @param array<int, Item> $inventoryItems
     */
    public function setInventoryItems(array $inventoryItems): void {
        $this->inventoryItems = $inventoryItems;
    }

    /**
     * @return array<int, Item>
     */
    public function getArmorItems(): array {
        return $this->armorItems;
    }

    /**
     * @param array<int, Item> $armorItems
     */
    public function setArmorItems(array $armorItems): void {
        $this->armorItems = $armorItems;
    }

    /**
     * @return string
     */
    public function getKnockbackProfile(): string {
        return $this->knockbackProfile;
    }

    /**
     * @param string $knockbackProfile
     */
    public function setKnockbackProfile(string $knockbackProfile): void {
        $this->knockbackProfile = $knockbackProfile;
    }

    /**
     * @param Player $player
     */
    public function applyOn(Player $player): void {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();

        $player->getInventory()->setContents($this->inventoryItems);
        $player->getArmorInventory()->setContents($this->armorItems);

        if ($this->name === self::BOXING || $this->name === self::SUMO) {
            $player->getEffects()->add(new EffectInstance(
                VanillaEffects::RESISTANCE(),
                Limits::INT32_MAX,
                12,
                false
            ));
        }

        (new PlayerKitAppliedEvent($player, $this))->call();
    }

    /**
     * If the name ends with 'Event' or 'FFA' then the kit is not duel available
     * This is used to prevent show this kit in the duel selector if it's not duel available
     *
     * @return bool
     */
    public function hasDuelAvailable(): bool {
        return !str_ends_with($this->name, 'FFA') && str_ends_with($this->name, 'Event');
    }
}