<?php

declare(strict_types=1);

namespace bitrule\practice\duel;

use pocketmine\item\Item;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\Player;
use function array_filter;
use function count;

final class DuelStatistics {

    /** @var int */
    private int $critics = 0;
    /** @var int */
    private int $totalPotions = 0;
    /** @var float */
    private float $damageDealt = 0.0;
    /** @var int */
    private int $kills = 0;
    /**
     * @var int The current kill streak of the player.
     */
    private int $currentKillStreak = 0;
    /**
     * @var int The highest kill streak achieved by the player.
     */
    private int $highestKillStreak = 0;
    /**
     * Total hits given to other players.
     * @var int
     */
    private int $totalHits = 0;
    /**
     * The current combo of the player.
     * @var int
     */
    private int $currentCombo = 0;
    /**
     * The highest combo achieved by the player.
     * @var int
     */
    private int $highestCombo = 0;

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

    /**
     * @return int
     */
    public function getKills(): int {
        return $this->kills;
    }

    /**
     * @param int $kills
     */
    public function setKills(int $kills): void {
        $this->kills = $kills;
    }

    /**
     * @return int
     */
    public function getCurrentKillStreak(): int {
        return $this->currentKillStreak;
    }

    /**
     * @param int $currentKillStreak
     */
    public function setCurrentKillStreak(int $currentKillStreak): void {
        $this->currentKillStreak = $currentKillStreak;
    }

    /**
     * @return int
     */
    public function getHighestKillStreak(): int {
        return $this->highestKillStreak;
    }

    /**
     * @param int $highestKillStreak
     */
    public function setHighestKillStreak(int $highestKillStreak): void {
        $this->highestKillStreak = $highestKillStreak;
    }

    /**
     * @return int
     */
    public function getTotalHits(): int {
        return $this->totalHits;
    }

    /**
     * Increments the total hits.
     */
    public function increaseTotalHits(): void {
        $this->totalHits++;
    }

    /**
     * @return int
     */
    public function getCurrentCombo(): int {
        return $this->currentCombo;
    }

    /**
     * Increments the current combo.
     */
    public function increaseCurrentCombo(): void {
        $this->currentCombo++;
    }

    /**
     * Resets the current combo.
     */
    public function resetCurrentCombo(): void {
        $this->currentCombo = 0;
    }

    /**
     * @return int
     */
    public function getHighestCombo(): int {
        return $this->highestCombo;
    }

    /**
     * @param int $highestCombo
     */
    public function setHighestCombo(int $highestCombo): void {
        $this->highestCombo = $highestCombo;
    }
}