<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\kit\Kit;
use bitrule\practice\Practice;
use JsonException;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use pocketmine\item\PotionType;
use pocketmine\item\SplashPotion;
use pocketmine\item\StringToItemParser;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function array_map;
use function is_array;
use function is_int;
use function is_string;

final class KitRegistry {
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

    /** @var array<string, Kit> */
    private array $kits = [];

    /**
     * This method is called when the plugin is enabled.
     */
    public function loadAll(): void {
        $config = new Config(Practice::getInstance()->getDataFolder() . 'kits.yml');
        foreach ($config->getAll() as $kitName => $kitData) {
            if (!is_string($kitName) || !is_array($kitData)) {
                throw new RuntimeException('Kit name is not a string or kit data is not an array');
            }

            if (!isset($kitData['inventoryItems'])) {
                throw new RuntimeException('Kit ' . $kitName . ' does not have inventory items');
            }

            if (!isset($kitData['armorItems'])) {
                throw new RuntimeException('Kit ' . $kitName . ' does not have armor items');
            }

            if (!isset($kitData['kbProfile'])) {
                throw new RuntimeException('Kit ' . $kitName . ' does not have a knockback profile');
            }

            $this->kits[$kitName] = new Kit(
                $kitName,
                array_map(fn(array $itemData) => self::parseItem($itemData), $kitData['inventoryItems']),
                array_map(fn(array $itemData) => self::parseItem($itemData), $kitData['armorItems']),
                $kitData['kbProfile']
            );
        }

        Practice::getInstance()->getLogger()->info(TextFormat::GREEN . 'Loaded ' . count($this->kits) . ' kit(s)');
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
        	'kbProfile' => $kit->getKnockbackProfile()
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

        if (isset($itemData['count'])) {
            $item->setCount($itemData['count']);
        }

        if ($item instanceof Durable && isset($itemData['damage'])) {
            $item->setDamage($itemData['damage']);
        }

        if ($item instanceof Potion || $item instanceof SplashPotion) {
//            if (!isset($itemData['potion-type'])) {
//                throw new RuntimeException('Potion type is not set');
//            }

            $potionTypeName = $itemData['potion-type'] ?? PotionType::STRONG_HEALING()->name;
            if (!is_string($potionTypeName)) {
                throw new RuntimeException('Potion type is not a string');
            }

            $item->setType(PotionType::getAll()[$potionTypeName] ?? throw new RuntimeException('Potion type ' . $potionTypeName . ' does not exist'));
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
        $itemData = [
        	'name' => $item->getVanillaName(),
        	'customName' => $item->getCustomName(),
        	'count' => $item->getCount(),
        	'lore' => $item->getLore(),
        	'enchantments' => array_map(
        	    fn(EnchantmentInstance $enchantment) => [EnchantmentIdMap::getInstance()->toId($enchantment->getType()), $enchantment->getLevel()],
        	    $item->getEnchantments()
        	)
        ];

        if ($item instanceof Durable) {
            $itemData['damage'] = $item->getDamage();
        } elseif ($item instanceof Potion || $item instanceof SplashPotion) {
            $itemData['potion-type'] = $item->getType()->name;
        }

        return $itemData;
    }
}