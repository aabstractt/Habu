<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\commands\ArenaMainCommand;
use bitrule\practice\commands\JoinQueueCommand;
use bitrule\practice\listener\defaults\PlayerInteractListener;
use bitrule\practice\listener\defaults\PlayerJoinListener;
use bitrule\practice\listener\defaults\PlayerQuitListener;
use bitrule\practice\manager\ArenaManager;
use bitrule\practice\manager\KitManager;
use bitrule\practice\manager\MatchManager;
use bitrule\practice\manager\ProfileManager;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;

final class Practice extends PluginBase {
    use SingletonTrait;

    protected function onEnable(): void {
        self::setInstance($this);

        $bootstrap = 'phar://' . $this->getServer()->getPluginPath() . $this->getName() . '.phar/vendor/autoload.php';
        if (!is_file($bootstrap)) {
            $this->getLogger()->error('Could not find autoload.php in plugin phar, directory: ' . $bootstrap);
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        require_once $bootstrap;

        $this->saveDefaultConfig();
        $this->saveResource('scoreboard.yml', true);

        ProfileManager::getInstance()->init();
        KitManager::getInstance()->loadAll();
        ArenaManager::getInstance()->init();

        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        $this->getServer()->getCommandMap()->registerAll('bitrule', [
            new ArenaMainCommand(),
            new JoinQueueCommand('joinqueue', 'Join a queue for a kit.', '/joinqueue <kit>')
        ]);

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function (): void {
            MatchManager::getInstance()->tickStages();
            ProfileManager::getInstance()->tickScoreboard();
        }), 20);
    }

    /**
     * @param string $key
     *
     * @return string
     */
    public static function getString(string $key): string {
        return is_string($string = self::getInstance()->getConfig()->getNested($key)) ? $string : '';
    }

    /**
     * @param string $key
     *
     * @return string[]
     */
    public static function getListString(string $key): array {
        return is_array($list = self::getInstance()->getConfig()->getNested($key)) ? $list : [];
    }

    /**
     * Replace placeholders in the text.
     * @param Player $player
     * @param string $identifier
     *
     * @return string|null
     */
    public static function replacePlaceholders(Player $player, string $identifier): ?string {
        if ($identifier === 'total_queue_count') return strval(MatchManager::getInstance()->getQueueCount(null));
        if ($identifier === 'total_match_count') return strval(MatchManager::getInstance()->getMatchCount(null));
        if ($identifier === 'online_players') return strval(count(self::getInstance()->getServer()->getOnlinePlayers()));

        $localProfile = ProfileManager::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) return null;

        if (str_starts_with($identifier, 'queue_')) {
            if (($matchQueue = $localProfile->getMatchQueue()) === null) return null;

            if ($identifier === 'queue_type') return $matchQueue->isRanked() ? 'Ranked' : 'Unranked';
            if ($identifier === 'queue_kit') return $matchQueue->getKitName() ?? 'None';
            if ($identifier === 'queue_duration') return gmdate('i:s', time() - $matchQueue->getTimestamp());
        }

        return null;
    }
}