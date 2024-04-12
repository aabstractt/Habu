<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\match\AbstractMatch;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\profile\LocalProfile;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;

final class ProfileRegistry {
    use SingletonTrait;

    public const LOBBY_SCOREBOARD = 'lobby';
    public const QUEUE_SCOREBOARD = 'queue';
    public const MATCH_STARTING_SCOREBOARD = 'match-starting';
    public const MATCH_PLAYING_SCOREBOARD = 'match-playing';
    public const MATCH_ENDING_SCOREBOARD = 'match-ending';

    /** @var array<string, LocalProfile> */
    private array $localProfiles = [];

    /** @var array<string, DuelProfile> */
    private array $duelProfiles = [];

    /**
     * @param string $xuid
     *
     * @return LocalProfile|null
     */
    public function getLocalProfile(string $xuid): ?LocalProfile {
        return $this->localProfiles[$xuid] ?? null;
    }

    /**
     * @param Player $player
     */
    public function addLocalProfile(Player $player): void {
        if (isset($this->localProfiles[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in local players list');
        }

        $this->localProfiles[$player->getXuid()] = $localProfile = new LocalProfile($player->getXuid(), $player->getName());

        $localProfile->joinLobby($player, true);
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
        $localProfile = $this->localProfiles[$player->getXuid()] ?? null;
        if ($localProfile === null) return;

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel !== null) {
            $duel->removePlayer($player, true);
            $duel->postRemovePlayer($player);
        }

        QueueRegistry::getInstance()->removeQueue($localProfile);

        unset($localProfile);
    }

    /**
     * @param string $xuid
     *
     * @return DuelProfile|null
     */
    public function getDuelProfile(string $xuid): ?DuelProfile {
        return $this->duelProfiles[$xuid] ?? null;
    }

    /**
     * @param Player        $player
     * @param AbstractMatch $match
     * @param bool          $spectator
     */
    public function addDuelProfile(Player $player, AbstractMatch $match, bool $spectator = false): void {
        if (isset($this->duelProfiles[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in duel players list');
        }

        $this->duelProfiles[$player->getXuid()] = $duelProfile = new DuelProfile(
            $player->getXuid(),
            $player->getName(),
            $match->getFullName(),
            !$spectator
        );

        if (!$spectator) return;

        $duelProfile->convertAsSpectator($match, true);
    }

    public function removeDuelProfile(Player $player): void {
        unset($this->duelProfiles[$player->getXuid()]);
    }

    /**
     * Tick the scoreboard for all players
     */
    public function tickScoreboard(): void {
        foreach ($this->localProfiles as $localProfile) {
            if (($scoreboard = $localProfile->getScoreboard()) === null) continue;

            $player = Server::getInstance()->getPlayerExact($localProfile->getName());
            if ($player === null || !$player->isOnline()) continue;

            $scoreboard->update($player, $localProfile);
        }
    }
}