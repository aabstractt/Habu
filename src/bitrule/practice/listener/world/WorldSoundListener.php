<?php

declare(strict_types=1);

namespace bitrule\practice\listener\world;

use pocketmine\event\Listener;
use pocketmine\event\world\WorldSoundEvent;
use pocketmine\world\sound\EntityAttackNoDamageSound;
use pocketmine\world\sound\EntityAttackSound;

final class WorldSoundListener implements Listener {

    /**
     * @param WorldSoundEvent $ev
     *
     * @priority NORMAL
     */
    public function onWorldSoundEvent(WorldSoundEvent $ev): void {
        $sound = $ev->getSound();
        if (!$sound instanceof EntityAttackSound && !$sound instanceof EntityAttackNoDamageSound) return;

        $ev->cancel();
    }
}