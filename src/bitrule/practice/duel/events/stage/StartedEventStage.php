<?php

declare(strict_types=1);

namespace bitrule\practice\duel\events\stage;

use bitrule\practice\duel\events\SumoEvent;
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KitRegistry;
use LogicException;
use function array_key_first;
use function array_rand;
use function count;
use function is_string;
use function shuffle;

final class StartedEventStage implements EventStage {

    /** @var int $round */
    private int $round = 1;
    /** @var int $roundTimeElapsed */
    private int $roundTimeElapsed = 0;

    /**
     * The first player xuid
     * @var string|null $firstPlayerXuid
     */
    private ?string $firstPlayerXuid = null;
    /**
     * The second player xuid
     * @var string|null $secondPlayerXuid
     */
    private ?string $secondPlayerXuid = null;

    /**
     * @param SumoEvent $event
     */
    public function update(SumoEvent $event): void {
        $this->roundTimeElapsed++;

        if ($this->roundTimeElapsed <= 3) {
            $event->broadcast('Round #' . $this->round . ' starting in ' . (3 - $this->roundTimeElapsed) . ' seconds');
        } elseif ($this->roundTimeElapsed === 4) {
            $event->broadcast('Round started!');
        }

        if ($this->roundTimeElapsed < 90) return;

        $this->end($event, null);
    }

    /**
     * @param string $xuid
     *
     * @return bool
     */
    public function isOpponent(string $xuid): bool {
        return $this->firstPlayerXuid === $xuid || $this->secondPlayerXuid === $xuid;
    }

    /**
     * @param SumoEvent   $event
     * @param string|null $whoDiedXuid
     */
    public function end(SumoEvent $event, ?string $whoDiedXuid): void {
        // TODO: I need know who won the round
        foreach ([$this->firstPlayerXuid, $this->secondPlayerXuid] as $xuid) {
            if ($xuid === null) continue;

            $player = DuelRegistry::getInstance()->getPlayerObject($xuid);
            if ($player === null) continue;

            Profile::setDefaultAttributes($player);

            if ($xuid !== $whoDiedXuid) {
                $player->teleport($player->getWorld()->getSpawnLocation());

                continue;
            }

            $event->quitPlayer($player, true);
        }

        $playersAlive = $event->getPlayersAlive();
        if (count($playersAlive) <= 1) {
            $event->end(count($playersAlive) === 0 ? null : $playersAlive[array_key_first($playersAlive)] ?? null);

            return;
        }

        $this->roundTimeElapsed = 0;
        $this->round++;

        shuffle($playersAlive);

        $firstKey = array_rand($playersAlive);
        if (!is_string($firstKey)) {
            throw new LogicException('Invalid key type');
        }

        $firstXuid = $playersAlive[$firstKey] ?? null;
        if ($firstXuid === null) {
            throw new LogicException('First player is not online');
        }

        $secondKey = array_rand($playersAlive);
        if (!is_string($secondKey)) {
            throw new LogicException('Invalid key type');
        }

        $secondXuid = $playersAlive[$secondKey] ?? null;
        if ($secondXuid === null) {
            throw new LogicException('Second player is not online');
        }

        $firstPlayer = DuelRegistry::getInstance()->getPlayerObject($firstXuid);
        if ($firstPlayer === null) {
            throw new LogicException('First player is not online');
        }

        $secondPlayer = DuelRegistry::getInstance()->getPlayerObject($secondXuid);
        if ($secondPlayer === null) {
            throw new LogicException('Second player is not online');
        }

        $this->firstPlayerXuid = $firstPlayer->getXuid();
        $this->secondPlayerXuid = $secondPlayer->getXuid();

        $arenaProperties = $event->getArenaProperties();
        if ($arenaProperties === null) {
            throw new LogicException('ArenaProperties is not set');
        }

        $kit = KitRegistry::getInstance()->getKit($arenaProperties->getPrimaryKit());
        if ($kit === null) {
            throw new LogicException('Kit not found');
        }

        foreach ([$firstPlayer, $secondPlayer] as $index => $player) {
            $player->teleport($index === 0 ? $arenaProperties->getFirstPosition() : $arenaProperties->getSecondPosition());

            $kit->applyOn($player);
        }
    }
}