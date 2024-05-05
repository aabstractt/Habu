<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\kit\Kit;
use bitrule\practice\Practice;
use JsonException;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\StringToItemParser;
use pocketmine\item\VanillaItems;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use RuntimeException;
use function array_map;
use function is_array;
use function is_int;
use function is_string;
use function strtoupper;

final class KitRegistry {
    use SingletonTrait;

    /** @var array<string, Kit> */
    private array $kits = [];

    /**
     * This method is called when the plugin is enabled.
     */
    public function loadAll(): void {
        $config = new Config(Practice::getInstance()->getDataFolder() . 'kits.yml');
        foreach ($config->getAll() as $kitName => $kitData) {
            if (!is_string($kitName) || !is_array($kitData)) {
                Practice::getInstance()->getLogger()->error('Invalid kit data');
                continue;
            }

            if (!isset($kitData['inventoryItems'])) {
                Practice::getInstance()->getLogger()->error('Kit ' . $kitName . ' does not have inventory items');
                continue;
            }

            if (!isset($kitData['armorItems'])) {
                Practice::getInstance()->getLogger()->error('Kit ' . $kitName . ' does not have armor items');
                continue;
            }

            if (!isset($kitData['kbProfile'])) {
                Practice::getInstance()->getLogger()->error('Kit ' . $kitName . ' does not have kb profile');
                continue;
            }

            $this->kits[$kitName] = new Kit(
                $kitName,
                array_map(fn(array $itemData) => self::parseItem($itemData), $kitData['inventoryItems']),
                array_map(fn(array $itemData) => self::parseItem($itemData), $kitData['armorItems']),
                $kitData['kbProfile']
            );
        }
    }

    /**
     * @param Kit $kit
     *
     * @throws JsonException
     */
    public function createKit(Kit $kit): void {
        $this->kits[$kit->getName()] = $kit;

        $config = new Config(Practice::getInstance()->getDataFolder() . 'kits.yml');
        $config->set($kit->getName(), [
        	'inventoryItems' => array_map(fn(Item $item) => self::writeItem($item), $kit->getInventoryItems()),
        	'armorItems' => array_map(fn(Item $item) => self::writeItem($item), $kit->getArmorItems()),
        	'kbProfile' => $kit->getKbProfile()
        ]);
        $config->save();
    }

    /**
     * @param string $name
     *
     * @return Kit|null
     */
    public function getKit(string $name): ?Kit {
        return $this->kits[$name] ?? null;
    }

    /**
     * @return array<string, Kit>
     */
    public function getKits(): array {
        return $this->kits;
    }

    /**
     * @param array $itemData
     *
     * @return Item
     */
    public static function parseItem(array $itemData): Item {
        $name = $itemData['name'] ?? null;
        if ($name === null) {
            throw new RuntimeException('Item name is not set');
        }

        $item = StringToItemParser::getInstance()->parse($name);
        if ($item === null) {
            throw new RuntimeException('Item ' . $name . ' does not exist');
        }

        if (isset($itemData['customName'])) {
            $item->setCustomName($itemData['customName']);
        }

        if (isset($itemData['lore'])) {
            $item->setLore($itemData['lore']);
        }

        if (isset($itemData['enchantments'])) {
            foreach ($itemData['enchantments'] as [$id, $level]) {
                if (!is_int($id)) {
                    throw new RuntimeException('Enchantment id is not an integer');
                }

                if (!is_int($level)) {
                    throw new RuntimeException('Enchantment level is not an integer');
                }

                $enchantment = EnchantmentIdMap::getInstance()->fromId($id);
                if ($enchantment === null) {
                    throw new RuntimeException('Enchantment ' . $id . ' does not exist');
                }

                $item->addEnchantment(new EnchantmentInstance($enchantment, $level));
            }
        }

        return $item;
    }

    /**
     * @param Item $item
     *
     * @return array
     */
    public static function writeItem(Item $item): array {
        return [
        	'name' => $item->getVanillaName(),
        	'customName' => $item->getCustomName(),
        	'lore' => $item->getLore(),
        	'enchantments' => array_map(
        	    fn(EnchantmentInstance $enchantment) => [EnchantmentIdMap::getInstance()->toId($enchantment->getType()), $enchantment->getLevel()],
        	    $item->getEnchantments()
        	)
        ];
    }
}