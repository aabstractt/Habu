<?php

declare(strict_types=1);

namespace bitrule\practice\match\impl;

use bitrule\practice\manager\ProfileManager;
use bitrule\practice\match\AbstractMatch;
use bitrule\practice\profile\DuelProfile;
use pocketmine\player\Player;
use function array_diff;
use function array_filter;
use function array_map;
use function array_search;
use function is_int;
use function str_starts_with;

final class SingleMatchImpl extends AbstractMatch {

    /** @var string[] */
    private array $players = [];

    /**
     * @param Player $player
     */
    public function teleportSpawn(Player $player): void {
        $spawnId = array_search($player->getXuid(), $this->players, true);
        if (!is_int($spawnId)) {
            throw new \RuntimeException('Player not found in match.');
        }

        $player->teleport(match ($spawnId) {
            0 => $this->arena->getFirstPosition(),
            1 => $this->arena->getSecondPosition(),
            default => $this->getWorld()->getSpawnLocation()
        });
    }

    /**
     * @param Player $player
     */
    public function removePlayer(Player $player): void {
        $this->players = array_diff($this->players, [$player->getXuid()]);
    }

    /**
     * @return DuelProfile[]
     */
    public function getEveryone(): array {
        return array_filter(
            array_map(
                fn (string $xuid) => ProfileManager::getInstance()->getDuelProfile($xuid),
                $this->players
            ),
            fn(?DuelProfile $duelProfile) => $duelProfile !== null
        );
    }

    /**
     * @param Player[] $totalPlayers
     */
    public function setup(array $totalPlayers): void {
        $this->players = array_map(
            fn(Player $player) => $player->getXuid(),
            $totalPlayers
        );
    }

    /**
     * @param Player $player
     *
     * @return Player|null
     */
    public function getOpponent(Player $player): ?Player {
        $spawnId = array_search($player->getXuid(), $this->players, true);
        if (!is_int($spawnId)) {
            throw new \RuntimeException('Player not found in match.');
        }

        $opponentXuid = match ($spawnId) {
            0 => $this->players[1],
            1 => $this->players[0],
            default => null
        };
        if ($opponentXuid === null) return null;

        return ProfileManager::getInstance()->getDuelProfile($opponentXuid)?->toPlayer();
    }

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        $parent = parent::replacePlaceholders($player, $identifier);
        if ($parent !== null) return $parent;

        if (str_starts_with($identifier, 'match_opponent')) {
            $opponent = $this->getOpponent($player);
            if ($opponent === null) return null;

            return $identifier === 'match_opponent_name' ? $opponent->getName() : (string) $opponent->getNetworkSession()->getPing();
        }

        return null;
    }
}