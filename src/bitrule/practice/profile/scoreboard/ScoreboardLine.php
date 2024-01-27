<?php

declare(strict_types=1);

namespace bitrule\practice\profile\scoreboard;

final class ScoreboardLine {

    /**
     * @param string      $identifier
     * @param string      $mainText
     * @param int         $currentSlot
     * @param int         $oldSlot
     * @param string|null $text
     * @param string|null $oldText
     */
    public function __construct(
        private readonly string $identifier,
        private readonly string $mainText,
        private int $currentSlot = 0,
        private int $oldSlot = 0,
        private ?string $text = null,
        private ?string $oldText = null
    ) {}

    /**
     * @return string
     */
    public function getIdentifier(): string {
        return $this->identifier;
    }

    /**
     * @return int
     */
    public function getOldSlot(): int {
        return $this->oldSlot;
    }

    /**
     * @return string|null
     */
    public function getText(): ?string {
        return $this->text;
    }

    /**
     * @return string|null
     */
    public function getOldText(): ?string {
        return $this->oldText;
    }

    /**
     * @return string
     */
    public function getMainText(): string {
        return $this->mainText;
    }

    /**
     * @param int         $slot
     * @param string|null $text
     *
     * @return UpdateResult
     */
    public function update(int $slot, ?string $text): UpdateResult {
        if ($slot === $this->currentSlot && $this->text === $text) return UpdateResult::NOT_UPDATED;

        $this->oldSlot = $this->currentSlot;
        $this->currentSlot = $slot;
        $this->oldText = $this->text;
        $this->text = $text;

        if ($text === null) return UpdateResult::REMOVED;

        return $this->oldText === null ? UpdateResult::ADDED : UpdateResult::UPDATED;
    }
}