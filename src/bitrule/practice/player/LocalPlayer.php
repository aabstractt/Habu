<?php

declare(strict_types=1);

namespace bitrule\practice\player;

use bitrule\practice\arena\setup\NormalArenaSetup;

final class LocalPlayer {

    /** @var NormalArenaSetup|null */
    private ?NormalArenaSetup $arenaSetup = null;

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
     * @return NormalArenaSetup|null
     */
    public function getArenaSetup(): ?NormalArenaSetup {
        return $this->arenaSetup;
    }

    /**
     * @param NormalArenaSetup|null $arenaSetup
     */
    public function setArenaSetup(?NormalArenaSetup $arenaSetup): void {
        $this->arenaSetup = $arenaSetup;
    }
}