<?php

declare(strict_types=1);

namespace bitrule\practice\profile\scoreboard;

use bitrule\practice\manager\MatchManager;
use bitrule\practice\Practice;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

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
            $this->lines[$identifier] = new ScoreboardLine($identifier, 0, 0, $text, null);
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
     * @param Player $player
     *
     * @return array
     */
    public function update(Player $player): array {
        $this->show($player);

        $packets = [];
        $slot = 0;

        foreach ($this->lines as $identifier => $scoreboardLine) {
            $text = str_contains($identifier, 'nothing_') ? '' : Practice::replacePlaceholders($player, $identifier);

            $oldText = $scoreboardLine->getText();
            $updateResult = $scoreboardLine->update($slot, $text);
            if ($updateResult === UpdateResult::REMOVED) {
                $packets[] = self::buildScorePacket($slot, '', SetScorePacket::TYPE_REMOVE);

                continue;
            }

            if ($updateResult === UpdateResult::NOT_UPDATED) {
                $slot++;

                continue;
            }

            if ($text === null) continue;

            if ($oldText !== null) {
                $packets[] = self::buildScorePacket($scoreboardLine->getOldSlot(), $oldText, SetScorePacket::TYPE_REMOVE);

                echo 'Removed old' . PHP_EOL;
            }

            echo 'Adding new' . PHP_EOL;

            $packets[] = self::buildScorePacket($slot++, TextFormat::colorize($scoreboardLine->getMainText() . $scoreboardLine->getText()), SetScorePacket::TYPE_CHANGE);
        }

        usort($packets, fn(SetScorePacket $a, SetScorePacket $b) => $a->type > $b->type ? 0 : 1);
        //sort($packets);

        if (count($packets) > 0) {
            var_dump($packets);
        }

        return $packets;
    }

    /**
     * @param int    $slot
     * @param string $text
     * @param int    $type
     *
     * @return SetScorePacket
     */
    private static function buildScorePacket(int $slot, string $text, int $type): SetScorePacket {
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