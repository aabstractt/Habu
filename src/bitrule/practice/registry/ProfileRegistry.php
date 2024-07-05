<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\habu\ffa\HabuFFA;
use bitrule\practice\profile\Profile;
use bitrule\scoreboard\ScoreboardRegistry;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function round;

final class ProfileRegistry {
    use SingletonTrait;

    public const LOBBY_SCOREBOARD = 'lobby';
    public const QUEUE_SCOREBOARD = 'queue';
    public const MATCH_STARTING_SCOREBOARD = 'match-starting';
    public const MATCH_STARTING_PARTY_SCOREBOARD = 'match-starting-party';
    public const MATCH_PLAYING_SCOREBOARD = 'match-playing';
    public const MATCH_ENDING_SCOREBOARD = 'match-ending';

    /** @var array<string, Profile> */
    private array $profiles = [];

    /**
     * @param string $xuid
     *
     * @return Profile|null
     */
    public function getProfile(string $xuid): ?Profile {
        return $this->profiles[$xuid] ?? null;
    }

    /**
     * @param Player $player
     */
    public function addProfile(Player $player): void {
        if (isset($this->profiles[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in local players list');
        }

        $this->profiles[$player->getXuid()] = $profile = new Profile($player->getXuid(), $player->getName(), 1_000);

        $profile->applyDefaultAttributes($player, true);
    }

    /**
     * Called when a player quits the server
     * Remove the player from the local profiles list
     * Remove the player from the queue
     * And remove the player from the duel if is in one
     *
     * @param Player $player
     */
    public function quitPlayer(Player $player): void {
        $profile = $this->profiles[$player->getXuid()] ?? null;
        if ($profile === null) return;

        QueueRegistry::getInstance()->removeQueue($player);

        unset($this->profiles[$player->getXuid()]);
    }

    /**
     * Tick the scoreboard for all players
     */
    public function tickScoreboard(): void {
        foreach (Server::getInstance()->getOnlinePlayers() as $player) {
            if (!$player->isOnline()) continue;

            ScoreboardRegistry::getInstance()->update($player);

            $duelMember = HabuFFA::getInstance()->getPlayer($player->getXuid());
            if ($duelMember === null) {
                $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
                if ($duel === null) continue;

                $duelMember = $duel->getMember($player->getXuid());
                if ($duelMember === null) continue;
            }

            $duelMember->clearEverything(false);

            if ($duelMember->getEnderPearlCountdown() <= 0.0) continue;

            $remainingCountdown = $duelMember->getRemainingEnderPearlCountdown();
            if ($remainingCountdown > 0.0) {
                $player->getXpManager()->setXpAndProgressNoEvent((int) round($remainingCountdown), $remainingCountdown / 15);
            } else {
                $player->sendMessage(TextFormat::GREEN . 'Your enderpearl cooldown expired.');
                $player->getXpManager()->setXpAndProgressNoEvent(0, 0.0);

                $duelMember->setEnderPearlCountdown(0.0);
            }
        }
    }
}