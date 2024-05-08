<?php

declare(strict_types=1);

namespace bitrule\practice\kit;

use bitrule\practice\event\player\PlayerKitAppliedEvent;
use pocketmine\item\Item;
use pocketmine\player\Player;

final class Kit {

    public const BOXING = 'Boxing';
    public const SUMO = 'Sumo';

    /**
     * @param string $name
     * @param array<int, Item>  $inventoryItems
     * @param array<int, Item>  $armorItems
     * @param string $kbProfile
     */
    public function __construct(
        private readonly string $name,
        private array $inventoryItems,
        private array $armorItems,
        private string $kbProfile
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
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
    public function getKbProfile(): string {
        return $this->kbProfile;
    }

    /**
     * @param string $kbProfile
     */
    public function setKbProfile(string $kbProfile): void {
        $this->kbProfile = $kbProfile;
    }

    /**
     * @param Player $player
     */
    public function applyOn(Player $player): void {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();

        $player->getInventory()->setContents($this->inventoryItems);
        $player->getArmorInventory()->setContents($this->armorItems);

        (new PlayerKitAppliedEvent($player, $this))->call();
    }
}