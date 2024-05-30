<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelMember;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\TranslationKey;
use pocketmine\player\Player;
use RuntimeException;
use function array_filter;
use function array_map;
use function count;
use function implode;

final class PartyFFADuelImpl extends Duel {
    use SpectatingDuelTrait;

    /**
     * @param Player     $player
     * @param DuelMember $duelMember
     */
    public function processPlayerPrepare(Player $player, DuelMember $duelMember): void {
        $this->playersSpawn[$player->getXuid()] = count($this->playersSpawn) % 2 === 0 ? 0 : 1;

        $opponents = array_map(
            fn(DuelMember $member): string => $member->getName(),
            array_filter(
                $this->getAlive(),
                fn(DuelMember $member) => $member->getXuid() !== $player->getXuid()
            )
        );
        if (count($opponents) === 0) {
            throw new RuntimeException('Opponent not found.');
        }

        $player->sendMessage(TranslationKey::DUEL_OPPONENT_FOUND()->build(
            implode(', ', $opponents),
            $this->ranked ? 'Ranked' : 'Unranked',
            $this->kit->getName()
        ));
    }

    /**
     * This method is called when the match stage changes to Ending.
     * Usually, it is used to send the match results to the players.
     */
    public function end(): void {
        if ($this->ending) return;

        parent::end();

        $winnerMember = $this->getWinner();
        if ($winnerMember === null) return;

        $player = $winnerMember->toPlayer();
        if ($player === null || !$player->isOnline()) return;

        $endMessage = TranslationKey::PARTY_DUEL_FFA_END()->build(
            $player->getName(),
            array_map(
                fn(DuelMember $member): string => $member->getName(),
                array_filter(
                    $this->getEveryone(),
                    fn(DuelMember $member) => $member->getXuid() !== $player->getXuid()
                )
            ),
            $winnerMember->getDuelStatistics()->getDamageDealt()
        );

        foreach ($this->getEveryone() as $duelMember) {
            $instance = $duelMember->toPlayer();
            if ($instance === null || !$instance->isOnline()) continue;

            $instance->sendMessage($endMessage);
        }
    }

    /**
     * Remove a player from the match.
     * Check if the match can end.
     * Usually, this is checked when the player dies or leaves the match.
     *
     * @param Player $player The player to be removed from the match.
     * @param bool   $canEnd A flag indicating whether the match can end after the player is removed.
     */
    public function removePlayer(Player $player, bool $canEnd): void {
        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) {
            throw new RuntimeException('Player not found in the match.');
        }

        unset($this->playersSpawn[$player->getXuid()]);

        $duelMember = $this->getMember($player->getXuid());
        if ($duelMember === null) {
            throw new RuntimeException('Player not found in the match.');
        }

        if ($duelMember->isAlive() && !$this->ending) {
            $duelMember->convertAsSpectator($this, false);
        }

        if (!$canEnd || $this->ending) return;

//        $expectedPlayersAlive = $duelMember->isPlaying() > 2 ? 1 : 2;
        if (count($this->getAlive()) > 2) return;

//        $this->end();
    }
}