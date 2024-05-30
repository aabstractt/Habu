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
use pocketmine\Server;

final class KitSelectorForm extends SimpleForm {

    /**
     * @param bool $ranked
     */
    public function setup(bool $ranked): void {
        foreach (KitRegistry::getInstance()->getKits() as $kit) {
            $this->addButton(
                new Button(
                    TranslationKey::QUEUE_LADDER_SELECTOR()->build(
                        $kit->getName(),
                        QueueRegistry::getInstance()->getQueueCount($kit->getName()),
                        DuelRegistry::getInstance()->getDuelsCount($kit->getName())
                    )
                ),
                function (Player $source, ?int $buttonIndex) use ($ranked, $kit): void {
                    if ($buttonIndex === null) return;

                    Server::getInstance()->dispatchCommand(
                        $source,
                        'joinqueue ' . $kit->getName() . ' ' . ($ranked ? 'ranked' : 'unranked')
                    );
                }
            );
        }
    }
}