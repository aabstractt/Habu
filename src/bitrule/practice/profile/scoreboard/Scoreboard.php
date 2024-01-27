<?php

declare(strict_types=1);

namespace bitrule\practice\profile\scoreboard;

use bitrule\practice\Practice;
use bitrule\practice\profile\LocalProfile;
use pocketmine\network\mcpe\NetworkBroadcastUtils;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use function count;
use function str_contains;
use function usort;

final class Scoreboard {

    /** @var array<string, ScoreboardLine> */
    private array $lines = [];
    /** @var bool */
    private bool $showed = false;

    /**
     * @param string[] $defaultLines
     */
    public function load(array $defaultLines): void {
        foreach ($defaultLines as $identifier => $text) {
            $this->lines[$identifier] = new ScoreboardLine($identifier, $text);
        }
    }

    /**
     * @param Player $player
     */
    public function show(Player $player): void {
        if ($this->showed) return;

        $player->getNetworkSession()->sendDataPacket(SetDisplayObjectivePacket::create(
            SetDisplayObjectivePacket::DISPLAY_SLOT_SIDEBAR,
            'bitrule',
            TextFormat::colorize('&l&3Practice'),
            'dummy',
            SetDisplayObjectivePacket::SORT_ORDER_ASCENDING
        ));

        $this->showed = true;
    }

    /**
     * @param Player $player
     */
    public function hide(Player $player): void {
        $player->getNetworkSession()->sendDataPacket(RemoveObjectivePacket::create('bitrule'));
    }

    /**
     * @param Player       $player
     * @param LocalProfile $localProfile
     */
    public function update(Player $player, LocalProfile $localProfile): void {
        $packets = [];
        $slot = 0;

        foreach ($this->lines as $identifier => $scoreboardLine) {
            $updateResult = $scoreboardLine->update(
                $slot,
                str_contains($identifier, 'nothing_') ? '' : Practice::replacePlaceholders($player, $localProfile, $identifier)
            );

            if ($updateResult === UpdateResult::NOT_UPDATED) {
                $slot++;
            } elseif ($updateResult === UpdateResult::REMOVED || $scoreboardLine->getOldText() !== null) {
                $packets[] = self::buildScorePacket(
                    $updateResult === UpdateResult::REMOVED ? $slot : $scoreboardLine->getOldSlot(),
                    '',
                    SetScorePacket::TYPE_REMOVE
                );
            }

            if ($updateResult->nonUpdated()) continue;

            $packets[] = self::buildScorePacket(
                $slot++,
                TextFormat::colorize($scoreboardLine->getMainText() . $scoreboardLine->getText()),
                SetScorePacket::TYPE_CHANGE
            );
        }

        usort($packets, fn(SetScorePacket $a, SetScorePacket $b) => $a->type > $b->type ? 0 : 1);

        if (count($packets) === 0) return;

        NetworkBroadcastUtils::broadcastPackets([$player], $packets);
    }

    /**
     * @param int    $slot
     * @param string $text
     * @param int    $type
     *
     * @return SetScorePacket
     */
    public static function buildScorePacket(int $slot, string $text, int $type): SetScorePacket {
        $entry = new ScorePacketEntry();
        $entry->objectiveName = 'bitrule';
        $entry->score = $slot;
        $entry->scoreboardId = $slot;

        if ($type === SetScorePacket::TYPE_CHANGE) {
            $entry->type = ScorePacketEntry::TYPE_FAKE_PLAYER;
            $entry->customName = $text;
        }

        return SetScorePacket::create($type, [$entry]);
    }
}