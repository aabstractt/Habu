<?php

declare(strict_types=1);

namespace bitrule\practice\manager;

use bitrule\practice\Practice;
use bitrule\practice\profile\DuelProfile;
use bitrule\practice\profile\LocalProfile;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function count;

final class ProfileManager {
    use SingletonTrait;

    public const LOBBY_SCOREBOARD = 'lobby';
    public const QUEUE_SCOREBOARD = 'queue';
    public const MATCH_STARTING_SCOREBOARD = 'starting';
    public const MATCH_PLAYING_SCOREBOARD = 'playing';
    public const MATCH_ENDING_SCOREBOARD = 'ending';

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

        $this->localProfiles[$player->getXuid()] = new LocalProfile($player->getXuid(), $player->getName());

        Practice::setProfileScoreboard($player, self::LOBBY_SCOREBOARD);
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
     * @param Player $player
     * @param string $matchFullName
     */
    public function addDuelProfile(Player $player, string $matchFullName): void {
        if (isset($this->duelProfiles[$player->getXuid()])) {
            throw new RuntimeException('Player already exists in duel players list');
        }

        $this->duelProfiles[$player->getXuid()] = new DuelProfile($player->getXuid(), $player->getName(), $matchFullName);
    }

    /**
     * @param string $xuid
     */
    public function removeProfile(string $xuid): void {
        $localProfile = $this->localProfiles[$xuid] ?? null;
        if ($localProfile === null) return;

        QueueManager::getInstance()->removeQueue($localProfile);

        unset($localProfile, $this->duelProfiles[$xuid]);
    }

    /**
     * Tick the scoreboard for all players
     */
    public function tickScoreboard(): void {
        foreach ($this->localProfiles as $localProfile) {
            if (($scoreboard = $localProfile->getScoreboard()) === null) continue;

            $player = Server::getInstance()->getPlayerExact($localProfile->getName());
            if ($player === null || !$player->isOnline()) continue;

            $packets = $scoreboard->update($player, $localProfile);
            if (count($packets) === 0) continue;

            NetworkBroadcastUtils::broadcastPackets([$player], $packets);
        }
    }
}