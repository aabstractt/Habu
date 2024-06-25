<?php

declare(strict_types=1);

namespace bitrule\practice\form\duel;

use bitrule\parties\object\Party;
use bitrule\practice\duel\impl\PartyFFADuelImpl;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\ProfileRegistry;
use cosmicpe\form\entries\simple\Button;
use cosmicpe\form\SimpleForm;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;
use function count;

final class PartyDuelForm extends SimpleForm {

    /**
     * @param Party $party
     * @param bool  $team
     */
    public function setup(Party $party, bool $team): void {
        foreach (KitRegistry::getInstance()->getKits() as $kit) {
            if (!$kit->isPartyPlayable() || !$kit->hasDuelAvailable()) continue;

            $this->addButton(
                new Button('§u' . TextFormat::BOLD . $kit->getName() . "\n" . TextFormat::RESET . TextFormat::DARK_GRAY . 'Click to select!'),
                function (Player $source, ?int $buttonIndex) use ($party, $kit): void {
                    if ($buttonIndex === null) return;

                    $totalPlayers = [];
                    foreach ($party->getMembers() as $member) {
                        $profile = ProfileRegistry::getInstance()->getProfile($member->getXuid());
                        if ($profile === null) continue;

                        $player = Server::getInstance()->getPlayerExact($profile->getName());
                        if ($player === null || !$player->isOnline()) continue;

                        $totalPlayers[] = $player;
                    }

                    if (count($totalPlayers) < 2) {
                        $source->sendMessage(TextFormat::RED . 'Not enough players in the party.');

                        return;
                    }

                    $arenaProperties = ArenaRegistry::getInstance()->getRandomArena($kit);
                    if ($arenaProperties === null) {
                        $source->sendMessage(TextFormat::RED . 'No arenas found.');

                        return;
                    }

                    DuelRegistry::getInstance()->prepareDuel(
                        $totalPlayers,
                        new PartyFFADuelImpl(
                            $arenaProperties,
                            $kit,
                            Uuid::uuid4()->toString(),
                            false
                        )
                    );
                }
            );
        }
    }
}