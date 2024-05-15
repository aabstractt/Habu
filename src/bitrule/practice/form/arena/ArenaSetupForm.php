<?php

declare(strict_types=1);

namespace bitrule\practice\form\arena;

use bitrule\practice\arena\ArenaProperties;
use bitrule\practice\arena\setup\AbstractArenaSetup;
use bitrule\practice\kit\Kit;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\ProfileRegistry;
use cosmicpe\form\ClosableForm;
use cosmicpe\form\CustomForm;
use cosmicpe\form\entries\custom\CustomFormEntry;
use cosmicpe\form\entries\custom\DropdownEntry;
use Exception;
use pocketmine\form\FormValidationException;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;
use RuntimeException;
use function is_int;

final class ArenaSetupForm extends CustomForm implements ClosableForm {

    /**
     * The type of arena to set up.
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
        $options = [];
        foreach (KitRegistry::getInstance()->getKits() as $kit) {
            $options[] = $kit->getName();
        }

        $this->addEntry(
            new DropdownEntry(TextFormat::GRAY . 'Arena Kit', $options),
            function (Player $player, CustomFormEntry $entry, $value) use ($options): void {
                if (!is_int($value)) {
                    throw new FormValidationException('Please select a kit.');
                }

                if (($kitName = $options[$value] ?? null) === null) {
                    throw new FormValidationException('Please select a kit.');
                }

                if (KitRegistry::getInstance()->getKit($kitName) === null) {
                    throw new FormValidationException('The kit ' . $kitName . ' does not exist.');
                }

                $this->kitName = $kitName;
                $this->type = ArenaProperties::getArenaTypeByKit($kitName);
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

        $profile = ProfileRegistry::getInstance()->getprofile($player->getXuid());
        if ($profile === null) {
            throw new RuntimeException('Local player not found.');
        }

        try {
            $arenaSetup = AbstractArenaSetup::from($this->type);
            $arenaSetup->setName($this->world->getFolderName());
            $arenaSetup->setPrimaryKit($this->kitName);

            $arenaSetup->setup($player);

            $profile->setArenaSetup($arenaSetup);

            $player->sendMessage(TextFormat::GREEN . 'Arena setup started.');
        } catch (Exception $e) {
            $player->sendMessage(TextFormat::RED . 'Arena setup failed: ' . $e->getMessage());
        }
    }
}