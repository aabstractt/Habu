<?php

declare(strict_types=1);

namespace bitrule\practice\registry;

use bitrule\practice\kit\KnockbackProfile;
use bitrule\practice\Practice;
use Exception;
use InvalidArgumentException;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use Symfony\Component\Filesystem\Path;
use function array_keys;
use function count;
use function is_array;
use function is_string;
use function strtolower;

final class KnockbackRegistry {
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

    /** @var array<string, KnockbackProfile> */
    private array $knockbackProfiles = [];

    /**
     * Loads all knockback profiles from the config.
     *
     * @param Practice $plugin
     */
    public function loadAll(Practice $plugin): void {
        $configData = (new Config($plugin->getDataFolder() . 'knockback.yml'))->getAll();
        if (count($configData) === 0) {
            $plugin->getLogger()->warning('No knockback profiles found.');

            return;
        }

        foreach ($configData as $name => $data) {
            if (!is_array($data) || !isset($data['horizontal'], $data['vertical'], $data['highest_limit'], $data['hit_delay'])) {
                $plugin->getLogger()->warning('Invalid knockback profile data for ' . $name);

                continue;
            }

            if (!is_string($name)) {
                throw new InvalidArgumentException('Knockback profile name must be a string');
            }

            $this->knockbackProfiles[strtolower($name)] = new KnockbackProfile(
                $name,
                $data['horizontal'],
                $data['vertical'],
                $data['highest_limit'],
                $data['hit_delay']
            );
        }

        $plugin->getLogger()->info(TextFormat::GREEN . 'Loaded ' . count($this->knockbackProfiles) . ' knockback profile(s)');
    }

    /**
     * @param KnockbackProfile $knockbackProfile
     */
    public function registerNew(KnockbackProfile $knockbackProfile): void {
        $this->knockbackProfiles[strtolower($knockbackProfile->getName())] = $knockbackProfile;
    }

    /**
     * @param string $name
     */
    public function removeKnockback(string $name): void {
        unset($this->knockbackProfiles[strtolower($name)]);
    }

    /**
     * @param string $name
     *
     * @return KnockbackProfile|null
     */
    public function getKnockback(string $name): ?KnockbackProfile {
        return $this->knockbackProfiles[strtolower($name)] ?? null;
    }

    public function saveAll(): void {
        $config = new Config(Practice::getInstance()->getDataFolder() . 'knockback.yml');
        foreach (array_keys($config->getAll()) as $key) {
            if (!is_string($key)) {
                throw new InvalidArgumentException('Knockback profile name must be a string');
            }

            $config->remove($key);
        }

//        $config->save();

        foreach ($this->knockbackProfiles as $knockbackProfile) {
            $config->set($knockbackProfile->getName(), [
            	'horizontal' => $knockbackProfile->getHorizontal(),
            	'vertical' => $knockbackProfile->getVertical(),
            	'highest_limit' => $knockbackProfile->getHighestLimit(),
            	'hit_delay' => $knockbackProfile->getHitDelay()
            ]);
        }

        try {
            $config->save();
        } catch (Exception $e) {
            Practice::getInstance()->getLogger()->error('Failed to save knockback profiles: ' . $e->getMessage());
        }
    }
}