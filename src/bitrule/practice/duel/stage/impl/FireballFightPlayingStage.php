<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\arena\impl\FireballFightArenaProperties;
use bitrule\practice\duel\Duel;
use bitrule\practice\duel\DuelScoreboard;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\profile\Profile;
use bitrule\practice\TranslationKey;
use LogicException;
use pocketmine\block\Bed;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function str_starts_with;

final class FireballFightPlayingStage extends PlayingStage implements AnythingDamageStageListener, AttackDamageStageListener, BlockBreakStageListener, DuelScoreboard {

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
     * This is the scoreboard identifier of the arena.
     *
     * @return string
     */
    public function getScoreboardId(): string {
        return 'match-playing-fireball';
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
            $spawnId = $identifier === 'duel-teams-red' ? FireballFightArenaProperties::TEAM_RED_ID : FireballFightArenaProperties::TEAM_BLUE_ID;

            return ($bedDestroyed ? '0' : '1') . ($spawnId === $selfSpawnId ? TextFormat::GRAY . ' You' : '');
        }

        return null;
    }

    /**
     * This method is called when a player is damaged by another player.
     *
     * @param Duel                      $duel
     * @param Player                    $victim
     * @param EntityDamageByEntityEvent $ev
     */
    public function onEntityDamageByEntityEvent(Duel $duel, Player $victim, EntityDamageByEntityEvent $ev): void {
        $this->onAnythingDamageEvent($duel, $victim, $ev);
    }

    /**
     * This method is called when a player is damaged by anything
     * except another player.
     *
     * @param Duel              $duel
     * @param Player            $victim
     * @param EntityDamageEvent $ev
     */
    public function onAnythingDamageEvent(Duel $duel, Player $victim, EntityDamageEvent $ev): void {
        if ($victim->getHealth() - $ev->getFinalDamage() > 0) return;

        $victimProfile = $duel->getMember($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $attacker = $ev instanceof EntityDamageByEntityEvent ? $ev->getDamager() : null;

        $attackerProfile = $attacker instanceof Player ? $duel->getMember($attacker->getXuid()) : null;
        if ($attacker !== null && ($attackerProfile === null || !$attackerProfile->isAlive())) return;

        $victimSpawnId = $duel->getSpawnId($victim->getXuid());
        if ($victimSpawnId > 1) {
            throw new LogicException('Invalid spawn id: ' . $victimSpawnId);
        }

        $ev->cancel();

        $duel->teleportSpawn($victim);
        Profile::resetInventory($victim);

        $colorSupplier = fn(int $spawnId): string => $spawnId === 0 ? TextFormat::RED : TextFormat::BLUE;
        if ($attackerProfile === null || $attacker === null) {
            $duel->broadcastMessage(TranslationKey::FIREBALL_FIGHT_PLAYER_DEAD_WITHOUT_KILLER()->build(
                $colorSupplier($victimSpawnId) . $victim->getName()
            ));
        } else {
            $duel->broadcastMessage(TranslationKey::FIREBALL_FIGHT_PLAYER_DEAD()->build(
                $colorSupplier($victimSpawnId) . $victim->getName(),
                $colorSupplier($duel->getSpawnId($attackerProfile->getXuid())) . $attackerProfile->getName()
            ));

            $attackerProfile->getDuelStatistics()->setKills($attackerProfile->getDuelStatistics()->getKills() + 1);
        }

        $hasBeenBedDestroyed = $victimSpawnId === 0 ? $this->redBedDestroyed : $this->blueBedDestroyed;
        if ($hasBeenBedDestroyed) {
            $victimProfile->convertAsSpectator($duel, false);
        } else {
            $duel->getKit()->applyOn($victim);
        }
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
        if (!$arenaProperties instanceof FireballFightArenaProperties) {
            throw new LogicException('Invalid arena properties');
        }

        $spawnId = $duel->getSpawnId($player->getXuid());
        $opponentSpawnId = $spawnId === FireballFightArenaProperties::TEAM_RED_ID ? FireballFightArenaProperties::TEAM_BLUE_ID : FireballFightArenaProperties::TEAM_RED_ID;

        $bedSpawn = $spawnId === FireballFightArenaProperties::TEAM_RED_ID ? $arenaProperties->getFirstBedPosition() : $arenaProperties->getSecondBedPosition();
        $opponentBedSpawn = $opponentSpawnId === FireballFightArenaProperties::TEAM_RED_ID ? $arenaProperties->getFirstBedPosition() : $arenaProperties->getSecondBedPosition();
        $isValidBed = false;

        foreach ($block->getAffectedBlocks() as $affectedBlock) {
            if ($bedSpawn->equals($affectedBlock->getPosition())) {
                $ev->cancel();

                $player->sendMessage(TextFormat::RED . 'You cannot break your own bed');

                return;
            }

            if (!$opponentBedSpawn->equals($affectedBlock->getPosition())) continue;

            $isValidBed = true;

            break;
        }

        if (!$isValidBed) return;

        if ($spawnId === FireballFightArenaProperties::TEAM_RED_ID) {
            $this->blueBedDestroyed = true;
        } else {
            $this->redBedDestroyed = true;
        }

        $colorSupplier = fn(int $spawnId): string => $spawnId === 0 ? TextFormat::RED : TextFormat::BLUE;
        $duel->broadcastMessage(TextFormat::GOLD . TextFormat::BOLD . 'BED DESTROYED! ' . TextFormat::RESET . TextFormat::GRAY . 'The ' . $colorSupplier($opponentSpawnId) . ($opponentSpawnId === FireballFightArenaProperties::TEAM_RED_ID ? 'RED' : 'BLUE') . 'team\'s' . TextFormat::GRAY . ' bed has been destroyed by ' . $colorSupplier($spawnId) . $player->getName());

        $ev->setDrops([]);
    }
}