<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\arena\impl\BedFightArenaProperties;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\stage\StageScoreboard;
use bitrule\practice\profile\Profile;
use bitrule\practice\TranslationKey;
use LogicException;
use pocketmine\block\Bed;
use pocketmine\entity\Entity;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function str_starts_with;

final class BedFightPlayingStage extends KillablePlayingStage implements BlockBreakStageListener, StageScoreboard {

    /**
     * These properties allow us to know if some bed has been destroyed.
     * When a bed is destroyed, the player will be converted as a spectator
     * After his death.
     * @var bool
     */
    private bool $redBedDestroyed = false;
    /** @var bool */
    private bool $blueBedDestroyed = false;
    /**
     * This array allows us to know when a player is respawning and the time expected.
     * @var array<int, int>
     */
    private array $respawning = [];

    /**
     * @param Duel $duel
     */
    public function update(Duel $duel): void {
        parent::update($duel);

        if (!$duel->getStage() instanceof self) return;

        foreach ($this->respawning as $xuid => &$timeRemaining) {
            $timeRemaining--;
            if ($timeRemaining <= 0) {
                unset($this->respawning[$xuid]);
            }

            $duelMember = $duel->getMember((string) $xuid);
            if ($duelMember === null) continue;

            $player = $duelMember->toPlayer();
            if ($player === null || !$player->isOnline()) continue;

            if ($timeRemaining > 0) {
                $player->sendTitle(
                    TextFormat::RED . 'Respawning in ' . $timeRemaining . 's',
                    '',
                    0,
                    20,
                    0
                );

                continue;
            }

            $duel->teleportSpawn($player);

            Profile::resetInventory($player);
            $duel->getKit()->applyOn($player);

            $player->sendTitle(
                TextFormat::GREEN . 'Respawned!',
                '',
                0,
                20,
                0
            );
        }
    }

    /**
     * @param Duel        $duel
     * @param Player      $victim
     * @param Entity|null $attacker
     * @param int         $cause
     */
    public function killPlayer(Duel $duel, Player $victim, ?Entity $attacker, int $cause): void {
        $victimProfile = $duel->getMember($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $attackerProfile = $attacker instanceof Player ? $duel->getMember($attacker->getXuid()) : null;
        if ($attacker !== null && ($attackerProfile === null || !$attackerProfile->isAlive())) return;

        $victimSpawnId = $duel->getSpawnId($victim->getXuid());
        if ($victimSpawnId > 1) {
            throw new LogicException('Invalid spawn id: ' . $victimSpawnId);
        }

        if ($cause === EntityDamageEvent::CAUSE_VOID) $duel->teleportSpawn($victim);

        $colorSupplier = fn(int $spawnId): string => $spawnId === 0 ? TextFormat::RED : TextFormat::BLUE;
        if ($attackerProfile === null || $attacker === null) {
            $duel->broadcastMessage(TranslationKey::BED_FIGHT_PLAYER_DEAD_WITHOUT_KILLER()->build(
                $colorSupplier($victimSpawnId) . $victim->getName()
            ));
        } else {
            $duel->broadcastMessage(TranslationKey::BED_FIGHT_PLAYER_DEAD()->build(
                $colorSupplier($victimSpawnId) . $victim->getName(),
                $colorSupplier($duel->getSpawnId($attackerProfile->getXuid())) . $attackerProfile->getName()
            ));

            $attackerProfile->getDuelStatistics()->setKills($attackerProfile->getDuelStatistics()->getKills() + 1);
        }

        $hasBeenBedDestroyed = $victimSpawnId === 0 ? $this->redBedDestroyed : $this->blueBedDestroyed;
        if ($hasBeenBedDestroyed) {
            $victimProfile->convertAsSpectator($duel, false);

            return;
        }

        $duel->getKit()->applyOn($victim);

        $victim->setGamemode(GameMode::SPECTATOR);

        if ($duel->getKit()->getName() !== 'BedFight') return;

        $this->respawning[(int) $victim->getXuid()] = 5;
    }

    /**
     * @param Duel            $duel
     * @param Player          $player
     * @param BlockBreakEvent $ev
     */
    public function onBlockBreakEvent(Duel $duel, Player $player, BlockBreakEvent $ev): void {
        $block = $ev->getBlock();
        if (!$block instanceof Bed) return;

        $arenaProperties = $duel->getArenaProperties();
        if (!$arenaProperties instanceof BedFightArenaProperties) {
            throw new LogicException('Invalid arena properties');
        }

        $spawnId = $duel->getSpawnId($player->getXuid());
        $opponentSpawnId = $spawnId === BedFightArenaProperties::TEAM_RED_ID ? BedFightArenaProperties::TEAM_BLUE_ID : BedFightArenaProperties::TEAM_RED_ID;

        $bedSpawn = $spawnId === BedFightArenaProperties::TEAM_RED_ID ? $arenaProperties->getFirstBedPosition() : $arenaProperties->getSecondBedPosition();
        $opponentBedSpawn = $opponentSpawnId === BedFightArenaProperties::TEAM_RED_ID ? $arenaProperties->getFirstBedPosition() : $arenaProperties->getSecondBedPosition();
        $isValidBed = false;

        foreach ($block->getAffectedBlocks() as $affectedBlock) {
            $position = $affectedBlock->getPosition();

            $xEquals = $position->getFloorX() === $bedSpawn->getFloorX();
            $yEquals = $position->getFloorY() === $bedSpawn->getFloorY();
            $zEquals = $position->getFloorZ() === $bedSpawn->getFloorZ();
            if ($xEquals && $yEquals && $zEquals) {
                $ev->cancel();

                $player->sendMessage(TextFormat::RED . 'You cannot break your own bed');

                return;
            }

            $xEquals = $position->getFloorX() === $opponentBedSpawn->getFloorX();
            $yEquals = $position->getFloorY() === $opponentBedSpawn->getFloorY();
            $zEquals = $position->getFloorZ() === $opponentBedSpawn->getFloorZ();
            if (!$xEquals || !$yEquals || !$zEquals) continue;

            $isValidBed = true;

            break;
        }

        if (!$isValidBed) return;

        if ($spawnId === BedFightArenaProperties::TEAM_RED_ID) {
            $this->blueBedDestroyed = true;
        } else {
            $this->redBedDestroyed = true;
        }

        $colorSupplier = fn(int $spawnId): string => $spawnId === 0 ? TextFormat::RED : TextFormat::BLUE;
        $duel->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . 'BED DESTROYED! ' . TextFormat::RESET . TextFormat::GRAY . 'The ' . $colorSupplier($opponentSpawnId) . ($opponentSpawnId === BedFightArenaProperties::TEAM_RED_ID ? 'RED' : 'BLUE') . ' team\'s' . TextFormat::GRAY . ' bed has been destroyed by ' . $colorSupplier($spawnId) . $player->getName());

        foreach ($duel->getPlaying() as $duelMember) {
            $spawnId = $duel->getSpawnId($duelMember->getXuid());
            if ($spawnId === -1) {
                throw new LogicException('Spawn ID not found');
            }

            if ($spawnId !== $opponentSpawnId) continue;

            $lazyPlayer = $duelMember->toPlayer();
            if ($lazyPlayer === null || !$lazyPlayer->isOnline()) continue;

            $lazyPlayer->sendTitle(
                TextFormat::RED . 'BED DESTROYED!',
                TextFormat::GRAY . 'You can no longer respawn',
                10,
                40,
                10
            );
        }

        $ev->setDrops([]);

        $position = $player->getPosition();
        $duel->getWorld()->broadcastPacketToViewers(
            $player->getPosition(),
            PlaySoundPacket::create(
                'mob.enderdragon.death',
                $position->x,
                $position->y,
                $position->z,
                1.0,
                1.0
            )
        );
    }

    /**
     * This is the scoreboard identifier of the arena.
     *
     * @param Duel $duel
     *
     * @return string
     */
    public function getScoreboardId(Duel $duel): string {
        return 'match-playing-bed-fight';
    }

    /**
     * Replace placeholders in the text.
     *
     * @param Duel    $duel
     * @param Player  $source
     * @param Profile $profile
     * @param string  $identifier
     *
     * @return string|null
     */
    public function replacePlaceholders(Duel $duel, Player $source, Profile $profile, string $identifier): ?string {
        $duelMember = $duel->getMember($source->getXuid());
        if ($duelMember === null) return null;

        $selfSpawnId = $duel->getSpawnId($source->getXuid());
        if ($selfSpawnId === -1) {
            throw new LogicException('Spawn ID not found');
        }

        if ($identifier === 'duel-self-kills') return (string) $duelMember->getDuelStatistics()->getKills();

        if (str_starts_with($identifier, 'duel-teams-')) {
            $bedDestroyed = $identifier === 'duel-teams-red' ? $this->redBedDestroyed : $this->blueBedDestroyed;
            $spawnId = $identifier === 'duel-teams-red' ? BedFightArenaProperties::TEAM_RED_ID : BedFightArenaProperties::TEAM_BLUE_ID;

            return ($bedDestroyed ? '0' : '1') . ($spawnId === $selfSpawnId ? TextFormat::GRAY . ' You' : '');
        }

        return null;
    }
}