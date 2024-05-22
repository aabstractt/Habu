<?php

declare(strict_types=1);

namespace bitrule\practice\duel\stage\impl;

use bitrule\practice\duel\Duel;
use bitrule\practice\duel\impl\NormalDuelImpl;
use bitrule\practice\duel\impl\round\NormalRoundingDuelImpl;
use bitrule\practice\duel\stage\PlayingStage;
use bitrule\practice\duel\stage\StageScoreboard;
use bitrule\practice\profile\Profile;
use bitrule\practice\TranslationKey;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\player\Player;
use function abs;

final class BoxingPlayingStage extends PlayingStage implements AttackDamageStageListener, StageScoreboard {

    /**
     * This method is called when a player is damaged by another player.
     *
     * @param Duel                      $duel
     * @param Player                    $victim
     * @param EntityDamageByEntityEvent $ev
     */
    public function onEntityDamageByEntityEvent(Duel $duel, Player $victim, EntityDamageByEntityEvent $ev): void {
        $attacker = $ev->getDamager();
        if (!$attacker instanceof Player) return;

        $attackerProfile = $duel->getMember($attacker->getXuid());
        if ($attackerProfile === null || !$attackerProfile->isAlive()) return;

        $attackerDuelStatistics = $attackerProfile->getDuelStatistics();
        if ($attackerDuelStatistics->getTotalHits() < 100) return;

        $victimProfile = $duel->getMember($victim->getXuid());
        if ($victimProfile === null || !$victimProfile->isAlive()) return;

        $victimProfile->convertAsSpectator($duel, false);
    }

    /**
     * @return string
     */
    public function getScoreboardId(): string {
        return 'match-playing-boxing';
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
        if (!$duel instanceof NormalDuelImpl && !$duel instanceof NormalRoundingDuelImpl) return null;

        $duelMember = $duel->getMember($source->getXuid());
        if ($duelMember === null) return null;

        $opponent = $duel->getOpponent($source);
        if ($opponent === null) return null;

        $opponentDuelStatistics = $opponent->getDuelStatistics();
        $duelStatistics = $duelMember->getDuelStatistics();

        if ($identifier === 'duel-hits-difference') {
            $difference = $duelStatistics->getTotalHits() - $opponentDuelStatistics->getTotalHits();
            if ($difference === 0) {
                return TranslationKey::BOXING_DUEL_HITS_DIFFERENCE_NONE()->build();
            }

            if ($difference > 0) {
                return TranslationKey::BOXING_DUEL_HITS_DIFFERENCE_SELF()->build((string) $difference);
            }

            return TranslationKey::BOXING_DUEL_HITS_DIFFERENCE_OPPONENT()->build((string) abs($difference));
        }

        if ($identifier === 'duel-hits-diff-self') return (string) $duelStatistics->getTotalHits();
        if ($identifier === 'duel-hits-diff-opponent') return (string) $opponentDuelStatistics->getTotalHits();
        if ($identifier === 'duel-hits-status') {
            if ($opponentDuelStatistics->getCurrentCombo() > 0) {
                return TranslationKey::BOXING_DUEL_COMBO_OPPONENT()->build((string) $opponentDuelStatistics->getCurrentCombo());
            }

            if ($duelStatistics->getCurrentCombo() > 0) {
                return TranslationKey::BOXING_DUEL_COMBO_SELF()->build((string) $duelStatistics->getCurrentCombo());
            }

            return TranslationKey::BOXING_DUEL_COMBO_NONE()->build();
        }

        return null;
    }
}