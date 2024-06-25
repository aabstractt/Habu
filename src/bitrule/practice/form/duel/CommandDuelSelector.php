<?php

declare(strict_types=1);

namespace bitrule\practice\form\duel;

use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\QueueRegistry;
use bitrule\practice\TranslationKey;
use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\SimpleForm;
use pocketmine\player\Player;

final class CommandDuelSelector extends SimpleForm {

    /**
     * @param Player $target
     */
    public function setup(Player $target): void {
        foreach (KitRegistry::getInstance()->getKits() as $kit) {
            if (!$kit->hasDuelAvailable()) continue;

            $this->addButton(
                new Button(
                    TranslationKey::QUEUE_LADDER_SELECTOR()->build(
                        $kit->getName(),
                        QueueRegistry::getInstance()->getQueueCount($kit->getName()),
                        DuelRegistry::getInstance()->getDuelsCount($kit->getName())
                    )
                ),
                function (Player $source, ?int $buttonIndex) use ($target, $kit): void {
                    if ($buttonIndex === null) return;

                    DuelRegistry::getInstance()->addDuelInvite(
                        $source->getXuid(),
                        $target->getXuid(),
                        $kit->getName()
                    );

                    $source->sendMessage(TranslationKey::DUEL_SENT()->build($target->getName(), $kit->getName()));
                    $target->sendMessage(TranslationKey::DUEL_RECEIVED()->build($source->getName(), $kit->getName()));
                }
            );
        }
    }
}