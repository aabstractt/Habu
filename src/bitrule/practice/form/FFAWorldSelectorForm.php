<?php

declare(strict_types=1);

namespace bitrule\practice\form;

use bitrule\habu\ffa\HabuFFA;
use bitrule\practice\TranslationKey;
use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\Server;

final class FFAWorldSelectorForm extends SimpleForm {

    public function setup(): void {
        foreach (HabuFFA::getInstance()->getWorlds() as $worldName) {
            $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
            if ($world === null) continue;

            $this->addButton(
                new Button(
                    TranslationKey::FFA_WORLD_SELECTOR_TEXT()->build(
                        $worldName,
                        (string) count($world->getPlayers())
                    )
                ),
                function (Player $source, ?int $index) use ($world): void {
                    if ($index === null) return;

                    HabuFFA::getInstance()->join($source, $world->getFolderName(), true);
                }
            );
        }
    }
}