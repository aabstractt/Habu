<?php

declare(strict_types=1);

namespace bitrule\scoreboard;

enum UpdateResult {
    case ADDED;
    case UPDATED;
    case NOT_UPDATED;
    case REMOVED;

    /**
     * @return bool
     */
    public function nonUpdated(): bool {
        return $this == self::NOT_UPDATED || $this == self::REMOVED;
    }
}