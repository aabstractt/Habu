<?php

declare(strict_types=1);

namespace bitrule\practice\player;

use bitrule\practice\arena\setup\AbstractArenaSetup;

final class LocalPlayer {

    /** @var AbstractArenaSetup|null */
    private ?AbstractArenaSetup $arenaSetup = null;

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
}