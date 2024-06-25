<?php

declare(strict_types=1);

namespace bitrule\practice\profile;

use bitrule\practice\duel\events\stage\StartingEventStage;
use bitrule\practice\duel\events\SumoEvent;
use bitrule\practice\duel\stage\StageScoreboard;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\registry\QueueRegistry;
use bitrule\scoreboard\ScoreboardPlaceholders;
use pocketmine\player\Player;
use pocketmine\Server;
use function count;
use function gmdate;
use function str_starts_with;
use function time;

final class DefaultScoreboardPlaceholders implements ScoreboardPlaceholders {

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        if ($identifier === 'total-queue-count') return (string) (QueueRegistry::getInstance()->getQueueCount());
        if ($identifier === 'total-duel-count') return (string) (DuelRegistry::getInstance()->getDuelsCount());
        if ($identifier === 'online-players') return (string) (count(Server::getInstance()->getOnlinePlayers()));

        $sumoEvent = SumoEvent::getInstance();
        if ($identifier === 'event-countdown') {
            return $sumoEvent->getStage() instanceof StartingEventStage ? (string) $sumoEvent->getStage()->getCountdown() : null;
        }

        if ($identifier === 'event-players') return (string) count($sumoEvent->getPlayersAlive());

        if (str_starts_with($identifier, 'queue-')) {
            $queue = QueueRegistry::getInstance()->getQueueByPlayer($player);
            if ($queue === null) return null;

            if ($identifier === 'queue-type') return $queue->isRanked() ? 'Ranked' : 'Unranked';
            if ($identifier === 'queue-kit') return $queue->getKitName();
            if ($identifier === 'queue-duration') return gmdate('i:s', time() - $queue->getTimestamp());
        }

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel === null) return null;

        $result = $duel->replacePlaceholders($player, $identifier);
        if ($result !== null) return $result;

        $profile = ProfileRegistry::getInstance()->getProfile($player->getXuid());
        if ($profile === null) return null;

        $stage = $duel->getStage();
        if ($stage instanceof StageScoreboard) return $stage->replacePlaceholders($duel, $player, $profile, $identifier);

        return null;
    }
}