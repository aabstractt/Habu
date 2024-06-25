<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

final class DuelInvite {

    /**
     * @param string $xuid
     * @param string $kitName
     * @param int    $timestamp
     */
    public function __construct(
        private readonly string $xuid,
        private readonly string $kitName,
        private readonly int $timestamp
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
    public function getKitName(): string {
        return $this->kitName;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int {
        return $this->timestamp;
    }

    /**
     * @return bool
     */
    public function isExpired(): bool {
        return time() - $this->timestamp > 20;
    }
}