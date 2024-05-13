<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\arena\ScoreboardId;
use bitrule\practice\commands\ArenaMainCommand;
use bitrule\practice\commands\DurabilityCommand;
use bitrule\practice\commands\JoinQueueCommand;
use bitrule\practice\commands\KnockbackProfileCommand;
use bitrule\practice\listener\defaults\PlayerExhaustListener;
use bitrule\practice\listener\defaults\PlayerInteractListener;
use bitrule\practice\listener\defaults\PlayerJoinListener;
use bitrule\practice\listener\defaults\PlayerQuitListener;
use bitrule\practice\listener\entity\EntityDamageListener;
use bitrule\practice\listener\entity\EntityMotionListener;
use bitrule\practice\listener\entity\EntityTeleportListener;
use bitrule\practice\listener\match\DuelStartedListener;
use bitrule\practice\listener\match\PlayerKitAppliedListener;
use bitrule\practice\listener\match\SumoPlayerMoveListener;
use bitrule\practice\profile\LocalProfile;
use bitrule\practice\profile\scoreboard\Scoreboard;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\KnockbackRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\registry\QueueRegistry;
use Exception;
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
use function str_replace;
use function str_starts_with;
use function time;

final class Practice extends PluginBase {
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

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

        try {
            KitRegistry::getInstance()->loadAll();
        } catch (Exception $e) {
            $this->getLogger()->error('Error loading kits: ' . $e->getMessage());

            $this->getServer()->shutdown();
            return;
        }

        ArenaRegistry::getInstance()->loadAll();
        KnockbackRegistry::getInstance()->loadAll($this);

        // TODO: Default server listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerExhaustListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        // TODO: Match listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerKitAppliedListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityTeleportListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SumoPlayerMoveListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityMotionListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityDamageListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new DuelStartedListener(), $this);

        $this->getServer()->getCommandMap()->registerAll('bitrule', [
        	new ArenaMainCommand(),
        	new JoinQueueCommand('joinqueue', 'Join a queue for a kit.', '/joinqueue <kit>'),
        	new KnockbackProfileCommand(),
        	new DurabilityCommand('durability')
        ]);

        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function (): void {
                DuelRegistry::getInstance()->tickStages();
            }),
            20
        );

        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function (): void {
                ProfileRegistry::getInstance()->tickScoreboard();
            }),
            5
        );
    }

    // TODO: Make more clean this code
    public static function wrapMessage(string $messageKey, array $placeholders = []): string {
        $message = self::getInstance()->messagesConfig?->getNested($messageKey);
        if (!is_string($message)) {
            return TextFormat::colorize('&f<Missing message: &a\'' . $messageKey . '\'&f>');
        }

        foreach ($placeholders as $placeholder => $value) {
            $message = str_replace('<' . $placeholder . '>', (string) $value, $message);
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
        $localProfile = ProfileRegistry::getInstance()->getLocalProfile($player->getXuid());
        if ($localProfile === null) {
            throw new RuntimeException('Local profile not found for player: ' . $player->getName());
        }

        if (($scoreboard = $localProfile->getScoreboard()) !== null) {
            $scoreboard->hide($player); // TODO: Yes ??????
        }

        $localProfile->setScoreboard($scoreboard = new Scoreboard());

        // TODO: Please make this more clean :sad:
        $scoreboard->load(self::getInstance()->scoreboardLines[$identifier] ?? throw new RuntimeException('Scoreboard not found: ' . $identifier));
        $scoreboard->show($player);
        $scoreboard->update($player, $localProfile);
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
        if ($identifier === 'total-queue-count') return (string) (QueueRegistry::getInstance()->getQueueCount());
        if ($identifier === 'total-duel-count') return (string) (DuelRegistry::getInstance()->getDuelsCount());
        if ($identifier === 'online-players') return (string) (count(self::getInstance()->getServer()->getOnlinePlayers()));

        if (str_starts_with($identifier, 'queue-')) {
            if (($queue = $localProfile->getQueue()) === null) return null;

            if ($identifier === 'queue-type') return $queue->isRanked() ? 'Ranked' : 'Unranked';
            if ($identifier === 'queue-kit') return $queue->getKitName();
            if ($identifier === 'queue-duration') return gmdate('i:s', time() - $queue->getTimestamp());
        }

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel === null) return null;

        $result = $duel->replacePlaceholders($player, $identifier);
        if ($result !== null) return $result;

        $arena = $duel->getArena();
        if ($arena instanceof ScoreboardId) return $arena->replacePlaceholders($duel, $player, $localProfile, $identifier);

        return null;
    }
}