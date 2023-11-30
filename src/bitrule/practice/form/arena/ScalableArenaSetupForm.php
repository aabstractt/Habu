<?php

declare(strict_types=1);

namespace bitrule\practice\form\arena;

use bitrule\practice\arena\setup\ScalableArenaSetup;
use bitrule\practice\manager\ProfileManager;
use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\CustomFormEntry;
use cosmicpe\form\entries\custom\InputEntry;
use Exception;
use pocketmine\form\FormValidationException;
use pocketmine\math\Vector3;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

final class ScalableArenaSetupForm extends CustomForm {

    /**
     * @var Vector3|null
     */
    private ?Vector3 $startPosition = null;

    private int $spacingX = 0;
    private int $spacingZ = 0;

    public function __construct() {
        parent::__construct(TextFormat::BLUE . 'Scalable Arena Setup');
    }

    public function setup(): void {
        $this->addEntry(
            new InputEntry('Start Position'),
            function (Player $player, CustomFormEntry $entry, $value): void {
                if (!is_string($value)) {
                    throw new FormValidationException('Please enter a valid start position.');
                }

                [$x, $y, $z] = explode(':', $value);

                if (!is_numeric($x) || !is_numeric($y) || !is_numeric($z)) {
                    throw new FormValidationException('Please enter a valid start position.');
                }

                $this->startPosition = new Vector3(intval($x), intval($y), intval($z));
            }
        );

        $this->addEntry(
            new InputEntry('Spacing X'),
            function (Player $player, CustomFormEntry $entry, $value): void {
                if (!is_string($value)) {
                    throw new FormValidationException('Please enter a valid spacing X.');
                }

                if (!is_numeric($value)) {
                    throw new FormValidationException('Please enter a valid spacing X.');
                }

                $this->spacingX = intval($value);
            }
        );

        $this->addEntry(
            new InputEntry('Spacing Z'),
            function (Player $player, CustomFormEntry $entry, $value): void {
                if (!is_string($value)) {
                    throw new FormValidationException('Please enter a valid spacing Z.');
                }

                if (!is_numeric($value)) {
                    throw new FormValidationException('Please enter a valid spacing Z.');
                }

                $this->spacingZ = intval($value);
            }
        );
    }

    /**
     * This method is called after the all form entries have been submitted.
     *
     * @param Player $player
     */
    public function onPostSubmit(Player $player): void {
        if ($this->startPosition === null) {
            throw new FormValidationException('Start position is not set.');
        }

        if ($this->spacingX <= 0 || $this->spacingZ <= 0) {
            throw new FormValidationException('Spacing X or Z is not set.');
        }

        $localProfile = ProfileManager::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) {
            throw new FormValidationException('Local profile not found.');
        }

        $arenaSetup = $localProfile->getArenaSetup();
        if (!$arenaSetup instanceof ScalableArenaSetup) {
            throw new FormValidationException('Arena setup not found.');
        }

        $arenaSetup->setStartGridPoint($this->startPosition);
        $arenaSetup->setSpacingX($this->spacingX);
        $arenaSetup->setSpacingZ($this->spacingZ);

        try {
            $arenaSetup->setup($player);

            $player->sendMessage('Scalable setup for arena ' . $arenaSetup->getName() . ' is now completed.');
        } catch (Exception $e) {
            $player->sendMessage('Failed to setup arena ' . $arenaSetup->getName() . ': ' . $e->getMessage());
        }
    }
}