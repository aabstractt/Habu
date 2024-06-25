<?php

declare(strict_types=1);

namespace bitrule\practice\duel\events\stage;

use bitrule\practice\duel\events\SumoEvent;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class StartingEventStage implements EventStage {

    private int $attemptsToStart = 0;

    /**
     * @param int $countdown
     */
    public function __construct(private int $countdown) {}

    /**
     * @param SumoEvent $event
     */
    public function update(SumoEvent $event): void {
        if ($this->attemptsToStart > 3) {
            $event->broadcast(TextFormat::RED . 'The event has been cancelled due to lack of players');
            $event->disable();

            return;
        }

        $this->countdown--;

        if ($this->countdown % 10 === 0 || $this->countdown <= 5) {
            Server::getInstance()->broadcastMessage(TextFormat::YELLOW . 'The sumo event will start in ' . $this->countdown . ' seconds');
        }

        if ($this->countdown > 1) return;

        if (count($event->getPlayersAlive()) < 2) {
            $event->broadcast(TextFormat::RED . 'Not enough players to start the event');
            $event->setStage(new self(30));

            $this->attemptsToStart++;

            return;
        }

        $event->setStage($stage = new StartedEventStage());
        $stage->end($event, null);
    }

    /**
     * @return int
     */
    public function getCountdown(): int {
        return $this->countdown;
    }
}