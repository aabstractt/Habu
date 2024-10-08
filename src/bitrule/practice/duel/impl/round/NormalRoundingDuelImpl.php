<?php

declare(strict_types=1);

namespace bitrule\practice\duel\impl\round;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\duel\DuelMember;
use bitrule\practice\duel\impl\trait\OpponentDuelTrait;
use bitrule\practice\duel\impl\trait\SpectatingDuelTrait;
use bitrule\practice\kit\Kit;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\TranslationKey;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;
use RuntimeException;
use function count;

final class NormalRoundingDuelImpl extends RoundingDuel {
    use SpectatingDuelTrait;
    use OpponentDuelTrait;

    /**
     * @param Player                            $player
     * @param DuelMember $duelMember
     */
    public function processPlayerPrepare(Player $player, DuelMember $duelMember): void {
        $this->playersSpawn[$player->getXuid()] = count($this->playersSpawn);

        $opponentName = $this->getOpponentName($player->getXuid());
        if ($opponentName === null) {
            throw new RuntimeException('Opponent not found.');
        }

        $player->sendMessage(TranslationKey::DUEL_OPPONENT_FOUND()->build(
            $opponentName,
            $this->ranked ? 'Ranked' : 'Unranked',
            $this->kit->getName()
        ));
    }

    /**
     * Remove a player from the match.
     * Check if the match can end.
     * Usually is checked when the player died or left the match.
     *
     * @param Player $player
     */
    public function removePlayer(Player $player): void {
        $spawnId = $this->getSpawnId($player->getXuid());
        if ($spawnId === -1) return;

        unset($this->playersSpawn[$player->getXuid()]);

        if ($this->ending) return;

        $duelMember = $this->getMember($player->getXuid());
        if ($duelMember === null) {
            throw new RuntimeException('Player not found in the match.');
        }

        if ($duelMember->isAlive()) {
            $duelMember->convertAsSpectator($this, false);
        }

//        $expectedPlayersAlive = $spawnId > 2 ? 1 : 2;
        if (count($this->getAlive()) > 2) return;

        $this->end();
    }

    /**
     * Called when the duel stage changes
     * to Ending.
     */
    public function end(): void {
        foreach ($this->getPlaying() as $duelMember) {
            $player = $duelMember->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            $opponent = $this->getOpponent($player);
            if ($opponent === null) continue;

            Server::getInstance()->broadcastMessage(TranslationKey::DUEL_WINNER_BROADCAST()->build(
                $player->getName(),
                $opponent->getName(),
                $this->kit->getName()
            ));

            [$winElo, $lostElo] = $this->ranked ? DuelRegistry::calculateElo(
                $duelMember->getElo(),
                $opponent->getElo()
            ) : [0, 0];

            $selfDuelStatistics = $duelMember->getDuelStatistics();
            $opponentDuelStatistics = $opponent->getDuelStatistics();

            $player->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
                $opponent->getName(),
                $winElo > 0 ? TranslationKey::DUEL_ELO_CHANGES_WIN()->build((string) $winElo) : TextFormat::YELLOW . 'No changes',
                (string) $selfDuelStatistics->getCritics(),
                (string) $selfDuelStatistics->getDamageDealt(),
                (string) $opponentDuelStatistics->getCritics(),
                (string) $opponentDuelStatistics->getDamageDealt(),
            ));

            $opponentPlayer = $opponent->toPlayer();
            if ($opponentPlayer === null || !$opponentPlayer->isOnline()) continue;

            $opponentPlayer->sendMessage(TranslationKey::DUEL_END_STATISTICS_NORMAL()->build(
                $duelMember->getName(),
                $lostElo > 0 ? TranslationKey::DUEL_ELO_CHANGES_LOST()->build((string) $lostElo) : TextFormat::YELLOW . 'No changes',
                (string) $opponentDuelStatistics->getCritics(),
                (string) $opponentDuelStatistics->getDamageDealt(),
                (string) $selfDuelStatistics->getCritics(),
                (string) $selfDuelStatistics->getDamageDealt(),
            ));

            // Apply elo changes to the winner
            $profile = ProfileRegistry::getInstance()->getProfile($player->getXuid());
            if ($profile === null) continue;

            $profile->setElo($winElo);

            // Apply elo changes to the loser
            $profile = ProfileRegistry::getInstance()->getProfile($opponentPlayer->getXuid());
            if ($profile === null) continue;

            $profile->setElo($lostElo);
        }

        parent::end();
    }

    /**
     * @param string $xuid
     *
     * @return string|null
     */
    public function getOpponentName(string $xuid): ?string {
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
        if ($this->getSpawnId($player->getXuid()) === -1) return null;

        foreach ($this->getPlaying() as $duelMember) {
            if ($duelMember->getXuid() === $player->getXuid()) continue;

            return $duelMember;
        }

        return null;
    }

    /**
     * Create a round duel.
     *
     * @param bool                 $ranked
     * @param Kit                  $kit
     * @param RoundingInfo         $roundingInfo
     * @param ArenaProperties|null $arenaProperties
     *
     * @return self
     */
    public static function create(bool $ranked, Kit $kit, RoundingInfo $roundingInfo, ?ArenaProperties $arenaProperties = null): self {
        if ($arenaProperties === null) {
            $arenaProperties = ArenaRegistry::getInstance()->getRandomArena($kit);
        }

        if ($arenaProperties === null) {
            throw new RuntimeException('No arena found for the kit.');
        }

        return new self(
            $arenaProperties,
            $kit,
            $roundingInfo,
            Uuid::uuid4()->toString(),
            $ranked
        );
    }
}