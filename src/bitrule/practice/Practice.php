<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\commands\ArenaMainCommand;
use bitrule\practice\commands\JoinQueueCommand;
use bitrule\practice\listener\defaults\PlayerInteractListener;
use bitrule\practice\listener\defaults\PlayerJoinListener;
use bitrule\practice\listener\defaults\PlayerQuitListener;
use bitrule\practice\listener\EntityTeleportListener;
use bitrule\practice\listener\match\SumoPlayerMoveListener;
use bitrule\practice\manager\ArenaManager;
use bitrule\practice\manager\KitManager;
use bitrule\practice\manager\MatchManager;
use bitrule\practice\manager\ProfileManager;
use bitrule\practice\manager\QueueManager;
use bitrule\practice\profile\LocalProfile;
use bitrule\practice\profile\scoreboard\Scoreboard;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function count;
use function gmdate;
use function is_array;
use function is_file;
use function is_string;
use function str_starts_with;
use function time;

final class Practice extends PluginBase {
    use SingletonTrait;

    /** @var array<string, array<string, string>> */
    private array $scoreboardLines = [];

    private ?Config $messagesConfig = null;

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
        $this->saveResource('messages.yml', true);

        $config = new Config($this->getDataFolder() . 'scoreboard.yml');

        if (!is_array($scoreboardLine = $config->get('lines'))) {
            throw new RuntimeException('Invalid scoreboard.yml');
        }

        $this->scoreboardLines = $scoreboardLine;

        $this->messagesConfig = new Config($this->getDataFolder() . 'messages.yml');

        KitManager::getInstance()->loadAll();
        ArenaManager::getInstance()->init();

        // TODO: Default server listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        // TODO: Match listeners
        $this->getServer()->getPluginManager()->registerEvents(new EntityTeleportListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SumoPlayerMoveListener(), $this);

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
     * @param Player $player
     */
    public static function giveLobbyAttributes(Player $player): void {
        LocalProfile::resetInventory($player);

        // TODO: Give lobby items

        self::setProfileScoreboard($player, ProfileManager::LOBBY_SCOREBOARD);
    }

    public static function wrapMessage(string $messageKey, array $placeholders = []): string {
        $message = self::getInstance()->messagesConfig?->getNested($messageKey);
        if (!is_string($message)) {
            return TextFormat::colorize('&f<Missing message: &a\'' . $messageKey . '\'&f>');
        }

        foreach ($placeholders as $placeholder => $value) {
            $message = str_replace('<' . $placeholder . '>', $value, $message);
        }

        return TextFormat::colorize($message);

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
     * @param Player $player
     * @param string $identifier
     */
    public static function setProfileScoreboard(Player $player, string $identifier): void {
        $localProfile = ProfileManager::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) {
            throw new RuntimeException('Local profile not found for player: ' . $player->getName());
        }

        if (($scoreboard = $localProfile->getScoreboard()) !== null) {
            $scoreboard->hide($player); // TODO: Yes
        }

        $localProfile->setScoreboard($scoreboard = new Scoreboard());

        $scoreboard->load(self::getInstance()->scoreboardLines[$identifier] ?? throw new RuntimeException('Scoreboard not found: ' . $identifier));
        $scoreboard->show($player);
    }

    /**
     * Replace placeholders in the text.
     *
     * @param Player       $player
     * @param LocalProfile $localProfile
     * @param string       $identifier
     *
     * @return string|null
     */
    public static function replacePlaceholders(Player $player, LocalProfile $localProfile, string $identifier): ?string {
        if ($identifier === 'total_queue_count') return (string) (QueueManager::getInstance()->getQueueCount());
        if ($identifier === 'total_match_count') return (string) (MatchManager::getInstance()->getMatchCount());
        if ($identifier === 'online_players') return (string) (count(self::getInstance()->getServer()->getOnlinePlayers()));

        if (str_starts_with($identifier, 'queue_')) {
            if (($matchQueue = $localProfile->getMatchQueue()) === null) return null;

            if ($identifier === 'queue_type') return $matchQueue->isRanked() ? 'Ranked' : 'Unranked';
            if ($identifier === 'queue_kit') return $matchQueue->getKitName();
            if ($identifier === 'queue_duration') return gmdate('i:s', time() - $matchQueue->getTimestamp());
        }

        return MatchManager::getInstance()
            ->getMatchByPlayer($player->getXuid())
            ?->replacePlaceholders($player, $identifier);
    }
}