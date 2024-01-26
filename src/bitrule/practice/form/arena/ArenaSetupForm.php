<?php

declare(strict_types=1);

namespace bitrule\practice\form\arena;

use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\manager\KitManager;
use bitrule\practice\manager\ProfileManager;
use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\CustomFormEntry;
use cosmicpe\form\entries\custom\DropdownEntry;
use Exception;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use RuntimeException;
use function array_keys;
use function is_int;

final class ArenaSetupForm extends CustomForm {

    /**
     * The type of arena to setup.
     * @var string|null
     */
    private ?string $type = null;
    /**
     * The name of the kit to use for the arena.
     * @var string|null
     */
    private ?string $kitName = null;
    /**
     * The world of the arena.
     * @var World|null $world
     */
    private ?World $world = null;

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
    public function setup(World $world): void {
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
            new DropdownEntry(TextFormat::GRAY . 'Arena Kit', $options = array_keys(KitManager::getInstance()->getKits())),
            function (Player $player, CustomFormEntry $entry, $value) use ($options): void {
                if (!is_int($value)) {
                    throw new FormValidationException('Please select a kit.');
                }

                if (($kitName = $options[$value] ?? null) === null) {
                    throw new FormValidationException('Please select a kit.');
                }

                if (KitManager::getInstance()->getKit($kitName) === null) {
                    throw new FormValidationException('The kit ' . $kitName . ' does not exist.');
                }

                $this->kitName = $kitName;
            }
        );

        $this->world = $world;
    }

    /**
     * This method is called after the all form entries have been submitted.
     *
     * @param Player $player
     */
    public function onPostSubmit(Player $player): void {
        if ($this->type === null || $this->world === null || $this->kitName === null) {
            throw new RuntimeException('Arena setup form not initialized.');
        }

        $localProfile = ProfileManager::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) {
            throw new RuntimeException('Local player not found.');
        }

        $arenaSetup = AbstractArenaSetup::from($this->type);
        $arenaSetup->setName($this->world->getFolderName());
        $arenaSetup->addKit($this->kitName);

        try {
            $arenaSetup->setup($player);

            $localProfile->setArenaSetup($arenaSetup);

            $player->sendMessage(TextFormat::GREEN . 'Arena setup started.');
        } catch (Exception $e) {
            $player->sendMessage(TextFormat::RED . 'Arena setup failed: ' . $e->getMessage());
        }
    }
}