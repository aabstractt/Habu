<?php

declare(strict_types=1);

namespace bitrule\practice\form\arena;

use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\arena\setup\ScalableArenaSetup;
use bitrule\practice\manager\ArenaManager;
use bitrule\practice\manager\KitManager;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\Practice;
use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\CustomFormEntry;
use cosmicpe\form\entries\custom\DropdownEntry;
use cosmicpe\form\entries\custom\InputEntry;
use Exception;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use RuntimeException;

final class ArenaSetupForm extends CustomForm {

    /**
     * The type of arena to setup.
     * @var string|null
     */
    private ?string $type = null;
    /**
     * The name of the schematic to use for the arena.
     * @var string|null
     */
    private ?string $schematicName = null;
    /**
     * The name of the kit to use for the arena.
     * @var string|null
     */
    private ?string $kitName = null;

    /**
     * ArenaSetupForm constructor.
     */
    public function __construct() {
        parent::__construct(TextFormat::DARK_PURPLE . 'Arena Setup');
    }

    /**
     * This method is called when the form is initialized.
     * This is where you should add all the form entries.
     */
    public function setup(): void {
        $this->addEntry(
            new DropdownEntry(TextFormat::GRAY . 'Arena Type', $options = ['Normal', 'Bridge']),
            function (Player $player, CustomFormEntry $entry, $value) use ($options): void {
                if (!is_int($value)) {
                    throw new FormValidationException('Please select an arena type.');
                }

                if (!isset($options[$value])) {
                    throw new FormValidationException('Please select an arena type.');
                }

                $this->type = $options[$value];
            }
        );

        $this->addEntry(
            new InputEntry(TextFormat::GRAY . 'Schematic Name'),
            function (Player $player, CustomFormEntry $entry, $value): void {
                if (!is_string($value) || $value === '') {
                    throw new FormValidationException('Schematic name cannot be empty.');
                }

                if ($this->schematicName !== null) {
                    throw new FormValidationException('Schematic name already set.');
                }

                if (ArenaManager::getInstance()->getArena($value) !== null) {
                    throw new FormValidationException('An arena with that name already exists.');
                }

                $this->schematicName = $value;
        });

//        $this->addEntry(
//            new DropdownEntry(TextFormat::GRAY . 'Arena Kit', $options = array_keys(KitManager::getInstance()->getKits())),
//            function (Player $player, CustomFormEntry $entry, $value) use ($options): void {
//                if (!is_int($value)) {
//                    throw new FormValidationException('Please select a kit.');
//                }
//
//                if (($kitName = $options[$value] ?? null) === null) {
//                    throw new FormValidationException('Please select a kit.');
//                }
//
//                if (KitManager::getInstance()->getKit($kitName) === null) {
//                    throw new FormValidationException('The kit ' . $kitName . ' does not exist.');
//                }
//
//                $this->kitName = $kitName;
//            }
//        );
    }

    /**
     * This method is called after the all form entries have been submitted.
     *
     * @param Player $player
     */
    public function onPostSubmit(Player $player): void {
        if ($this->type === null || $this->schematicName === null) {
            throw new RuntimeException('Arena setup form not initialized.');
        }

        $localProfile = ProfileManager::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) {
            throw new RuntimeException('Local player not found.');
        }

        $arenaSetup = AbstractArenaSetup::from($this->type);
        $arenaSetup->setName($this->schematicName);

        if (!$arenaSetup instanceof ScalableArenaSetup) {
            try {
                $arenaSetup->setup($player);
            } catch (Exception $e) {
                $player->sendMessage(TextFormat::RED . 'Arena setup failed: ' . $e->getMessage());

                return;
            }
        }

        $localProfile->setArenaSetup($arenaSetup);

        $player->sendMessage(TextFormat::GREEN . 'Arena setup started.');

        if ($arenaSetup->isStarted()) return;

        $form = new ScalableArenaSetupForm();
        $form->setup();

        Practice::getInstance()->getScheduler()->scheduleDelayedTask(
            new ClosureTask(fn() => $player->sendForm($form)),
        10
        );
    }
}