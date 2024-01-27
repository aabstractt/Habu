<?php

declare(strict_types=1);

namespace bitrule\practice\match;

use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\Player;

final class MatchStatistics {

    /** @var int */
    private int $critics = 0;
    /** @var int */
    private int $totalPotions = 0;
    /** @var float */
    private float $damageDealt = 0.0;

    /**
     * @return int
     */
    public function getCritics(): int {
        return $this->critics;
    }

    /**
     * Increments the total critics.
     */
    public function addCritic(): void {
        $this->critics++;
    }

    /**
     * @return int
     */
    public function getTotalPotions(): int {
        return $this->totalPotions;
    }

    /**
     * @param Player $player
     */
    public function calculateTotalPotions(Player $player): void {
        $this->totalPotions = count(array_filter(
            $player->getInventory()->getContents(),
            fn(Item $item) => $item->getTypeId() === ItemTypeIds::SPLASH_POTION
        ));
    }

    /**
     * @return float
     */
    public function getDamageDealt(): float {
        return $this->damageDealt;
    }

    /**
     * Increments the total damage dealt.
     *
     * @param float $damageDealt
     */
    public function increaseDamageDealt(float $damageDealt): void {
        $this->damageDealt += $damageDealt;
    }
}