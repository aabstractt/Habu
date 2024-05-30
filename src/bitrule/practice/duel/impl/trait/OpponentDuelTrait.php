<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\trait;

use bitrule\practice\duel\DuelMember;
use bitrule\practice\duel\impl\NormalDuelImpl;
use pocketmine\player\Player;
use RuntimeException;
use function str_starts_with;

trait OpponentDuelTrait {

    /**
     * TODO: Move this to an trait
     *
     * @param string $xuid
     *
     * @return string|null
     */
    public function getOpponentName(string $xuid): ?string {
        if (!$this instanceof NormalDuelImpl) {
            throw new RuntimeException('This trait can only be used in NormalDuelImpl class.');
        }

        if ($this->getSpawnId($xuid) === -1) return null;

        foreach ($this->getPlaying() as $duelMember) {
            if ($duelMember->getXuid() === $xuid) continue;

            return $duelMember->getName();
        }

        return null;
    }

    /**
     * @param Player $player
     *
     * @return DuelMember|null
     */
    public function getOpponent(Player $player): ?DuelMember {
        if (!$this instanceof NormalDuelImpl) {
            throw new RuntimeException('This trait can only be used in NormalDuelImpl class.');
        }

        if ($this->getSpawnId($player->getXuid()) === -1) return null;

        foreach ($this->getPlaying() as $duelMember) {
            if ($duelMember->getXuid() === $player->getXuid()) continue;

            return $duelMember;
        }

        return null;
    }

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        if (!$this instanceof NormalDuelImpl) {
            throw new RuntimeException('This trait can only be used in NormalDuelImpl class.');
        }

        // TODO: I dont think this going to work... Idk
        $parent = parent::replacePlaceholders($player, $identifier);
        if ($parent !== null) return $parent;

        if (!str_starts_with($identifier, 'duel-opponent')) return null;

        $opponent = $this->getOpponent($player);
        if ($opponent === null) return null;

        $instance = $opponent->toPlayer();
        if ($instance === null || !$instance->isOnline()) return null;

        return $identifier === 'duel-opponent-name' ? $opponent->getName() : (string) $instance->getNetworkSession()->getPing();
    }
}