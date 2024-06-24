<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelMember;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\TranslationKey;
use bitrule\scoreboard\ScoreboardRegistry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function array_filter;
use function array_keys;
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

        ScoreboardRegistry::getInstance()->apply($player, 'match-starting-party');
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
            implode(', ', array_map(
                fn(DuelMember $member): string => $member->getName(),
                array_filter(
                    $this->getEveryone(),
                    fn(DuelMember $member) => $member->getXuid() !== $player->getXuid()
                )
            )),
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
     */
    public function removePlayer(Player $player): void {
        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) {
            throw new RuntimeException('Player not found in the match.');
        }

        unset($this->playersSpawn[$player->getXuid()]);

        if ($this->ending) return;

        $duelMember = $this->getMember($player->getXuid());
        if ($duelMember === null) {
            throw new RuntimeException('Player not found in the match.');
        }

        if ($duelMember->isAlive()) {
            $duelMember->convertAsSpectator($this, false);
        }

//        $expectedPlayersAlive = $duelMember->isPlaying() > 2 ? 1 : 2;
        if (count($this->getAlive()) > 2) return;

//        $this->end();
    }

    /**
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Player $player, string $identifier): ?string {
        $result = parent::replacePlaceholders($player, $identifier);
        if ($result !== null) return $result;

        $opponentId = match ($identifier) {
            'first-opponent' => 0,
            'second-opponent' => 1,
            'third-opponent' => 2,
            default => null
        };
        if ($opponentId === null) return null;

        $filtered = array_filter(
            $this->getAlive(),
            fn(DuelMember $member) => $member->getXuid() !== $player->getXuid()
        );
        if (count($filtered) === 0) return null;

        $opponentXuid = array_keys($filtered)[$opponentId] ?? null;
        if ($opponentXuid === null) return null;

        $duelMember = $this->getMember((string) $opponentXuid);
        if ($duelMember === null || !$duelMember->isPlaying()) return null;

        $suffix = '';
        if (count($filtered) > 3 && $opponentId === 2) {
            $suffix = TextFormat::GRAY . ' (+' . (count($filtered) - 3) . ')';
        }

        return $duelMember->getName() . $suffix;
    }
}