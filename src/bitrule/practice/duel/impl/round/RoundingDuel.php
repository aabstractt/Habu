<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\round;

use bitrule\practice\arena\AbstractArena;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\SpectatingDuelTrait;
use bitrule\practice\kit\Kit;
use bitrule\practice\Practice;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\registry\DuelRegistry;
use Exception;
use pocketmine\player\Player;
use function array_filter;
use function array_map;

abstract class RoundingDuel extends Duel {
    use SpectatingDuelTrait;

    /** @var RoundingInfo $roundingInfo */
    protected RoundingInfo $roundingInfo;

    /**
     * @param AbstractArena $arena
     * @param Kit           $kit
     * @param RoundingInfo  $roundingInfo
     * @param int           $id
     * @param bool          $ranked
     */
    public function __construct(AbstractArena $arena, Kit $kit, RoundingInfo $roundingInfo, int $id, bool $ranked) {
        parent::__construct($arena, $kit, $id, $ranked);

        $this->roundingInfo = $roundingInfo;
    }

    /**
     * This method is called when the match stage change to Ending.
     * Usually is used to send the match results to the players.
     */
    public function end(): void {
        if ($this->hasSomeoneDisconnected()) {
            $this->end();

            return;
        }

        $winnerXuid = $this->roundingInfo->findWinner();
        $winnerDuelProfile = $winnerXuid !== null ? $this->players[$winnerXuid] ?? null : null;
        if ($winnerDuelProfile !== null) {
            $this->end();

            return;
        }

        $spectators = $this->getSpectators();
        $players = $this->getPlayers();

        $this->postEnd();

        foreach ($players as $duelProfile) {
            if (!$duelProfile->isAlive()) continue;

            $player = $duelProfile->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            $this->roundingInfo->increaseWin($duelProfile->getXuid());
        }

        try {
            DuelRegistry::getInstance()->createDuel(
                array_filter(
                    array_map(fn (DuelProfile $duelProfile) => $duelProfile->toPlayer(), $players),
                    fn(?Player $player) => $player !== null && $player->isOnline()
                ),
                array_filter(
                    array_map(fn (DuelProfile $duelProfile) => $duelProfile->toPlayer(), $spectators),
                    fn(?Player $player) => $player !== null && $player->isOnline()
                ),
                $this->kit,
                $this->ranked,
                $this->roundingInfo
            );
        } catch (Exception $e) {
            Practice::getInstance()->getLogger()->error($e->getMessage());

            $this->end();
        }
    }

    /**
     * Let the server know if the duel
     * can be re-duel.
     *
     * @param DuelProfile[] $players
     * @return bool
     */
    abstract protected function canReDuel(array $players): bool;
}