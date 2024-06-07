<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\round;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\asyncio\FileDeleteAsyncTask;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelMember;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\Habu;
use bitrule\practice\kit\Kit;
use bitrule\practice\registry\DuelRegistry;
use Exception;
use pocketmine\player\Player;
use pocketmine\Server;
use function array_filter;
use function array_map;
use function count;

abstract class RoundingDuel extends Duel {
    use SpectatingDuelTrait;

    /** @var RoundingInfo $roundingInfo */
    protected RoundingInfo $roundingInfo;
    /** @var bool */
    protected bool $ended = false;

    /**
     * @param ArenaProperties $arenaProperties
     * @param Kit             $kit
     * @param RoundingInfo    $roundingInfo
     * @param string          $uniqueId
     * @param bool            $ranked
     */
    public function __construct(ArenaProperties $arenaProperties, Kit $kit, RoundingInfo $roundingInfo, string $uniqueId, bool $ranked) {
        parent::__construct($arenaProperties, $kit, $uniqueId, $ranked);

        $this->roundingInfo = $roundingInfo;
    }

    /**
     * This method is called when the match stage change to Ending.
     * Usually is used to send the match results to the players.
     */
    public function end(): void {
        $this->roundingInfo->registerWorld($this->getFullName());

        if ($this->hasSomeoneDisconnected()) {
            parent::end();
            $this->ended = true;

            return;
        }

        $duelMembers = $this->getPlaying();
        foreach ($duelMembers as $duelMember) {
            if (!$duelMember->isAlive()) continue;

            $player = $duelMember->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            $this->roundingInfo->increaseWin($duelMember->getXuid());
        }

        $winnerXuid = $this->roundingInfo->getWinner();
        $winnerDuelMember = $winnerXuid !== null ? $this->members[$winnerXuid] ?? null : null;
        if ($winnerDuelMember !== null) {
            parent::end();

            $this->ended = true;

            return;
        }

        $literalSpectators = array_filter(
            $this->getSpectators(),
            fn(DuelMember $duelMember) => !$duelMember->isPlaying()
        );

        $this->postEnd();

        try {
            $duelRegistry = DuelRegistry::getInstance();
            $duelRegistry->prepareDuel(
                totalPlayers: array_filter(
                    array_map(fn (DuelMember $duelMember) => $duelMember->toPlayer(), $duelMembers),
                    fn(?Player $player) => $player !== null && $player->isOnline()
                ),
                duel: NormalRoundingDuelImpl::create($this->ranked, $this->kit, $this->roundingInfo, $this->arenaProperties),
                onCompletion: function (Duel $duel) use ($literalSpectators): void {
                    $spectators = array_filter(
                        array_map(fn (DuelMember $duelMember) => $duelMember->toPlayer(), $literalSpectators),
                        fn(?Player $player) => $player !== null && $player->isOnline()
                    );
                    if (count($spectators) === 0) {
                        Habu::getInstance()->getLogger()->info('No spectators found for the duel.');

                        return;
                    }

                    foreach ($spectators as $spectator) {
                        $duel->joinSpectator($spectator);
                    }
                }
            );
        } catch (Exception $e) {
            Habu::getInstance()->getLogger()->error($e->getMessage());

            parent::end();
            $this->ended = true;
        }
    }

    /**
     * This method is called when the countdown ends.
     * Usually is used to delete the world
     * and teleport the players to the spawn point.
     */
    public function postEnd(): void {
        parent::postEnd();

        if (!$this->ended) return;

        foreach ($this->roundingInfo->getWorlds() as $worldName) {
            Server::getInstance()->getAsyncPool()->submitTask(new FileDeleteAsyncTask(
                Server::getInstance()->getDataPath() . 'worlds/' . $worldName
            ));
        }
    }
}