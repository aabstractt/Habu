<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\impl\BridgeArenaProperties;
use bitrule\practice\arena\impl\FireballFightArenaProperties;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\stage\impl\BoxingPlayingStage;
use bitrule\practice\duel\stage\impl\BridgePlayingStage;
use bitrule\practice\duel\stage\impl\DefaultPlayingStage;
use bitrule\practice\duel\stage\impl\FireballFightPlayingStage;

abstract class PlayingStage implements AbstractStage {

    /** @var int */
    private int $seconds = 0;

    /**
     * Using this method, you can update the stage of the match.
     *
     * @param Duel $duel
     */
    public function update(Duel $duel): void {
        if (!$duel->isLoaded()) {
            throw new \RuntimeException('Match is not loaded.');
        }

        $this->seconds++;
    }

    /**
     * @return int
     */
    public function getSeconds(): int {
        return $this->seconds;
    }

    /**
     * @param ArenaProperties $arenaProperties
     *
     * @return self
     */
    public static function create(ArenaProperties $arenaProperties): self {
        if ($arenaProperties->getPrimaryKit() === 'Boxing') return new BoxingPlayingStage();
        if ($arenaProperties instanceof BridgeArenaProperties) return new BridgePlayingStage();
        if ($arenaProperties instanceof FireballFightArenaProperties) return new FireballFightPlayingStage();

        return new DefaultPlayingStage();
    }
}