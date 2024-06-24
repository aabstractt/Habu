<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\commands\ArenaMainCommand;
use bitrule\practice\commands\DurabilityCommand;
use bitrule\practice\commands\EventsMainCommand;
use bitrule\practice\commands\JoinQueueCommand;
use bitrule\practice\commands\KnockbackProfileCommand;
use bitrule\practice\commands\LeaveQueueCommand;
use bitrule\practice\duel\events\SumoEvent;
use bitrule\practice\listener\defaults\PlayerExhaustListener;
use bitrule\practice\listener\defaults\PlayerInteractListener;
use bitrule\practice\listener\defaults\PlayerJoinListener;
use bitrule\practice\listener\defaults\PlayerQuitListener;
use bitrule\practice\listener\entity\EntityDamageListener;
use bitrule\practice\listener\entity\EntityMotionListener;
use bitrule\practice\listener\entity\EntityTeleportListener;
use bitrule\practice\listener\entity\ProjectileLaunchListener;
use bitrule\practice\listener\match\PlayerKitAppliedListener;
use bitrule\practice\listener\match\SumoPlayerMoveListener;
use bitrule\practice\listener\party\PartyCreateListener;
use bitrule\practice\listener\party\PartyDisbandListener;
use bitrule\practice\listener\party\PartyTransferListener;
use bitrule\practice\listener\world\BlockBreakListener;
use bitrule\practice\listener\world\WorldSoundListener;
use bitrule\practice\profile\DefaultScoreboardPlaceholders;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\KnockbackRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\scoreboard\ScoreboardRegistry;
use Exception;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\utils\TextFormat;
use RuntimeException;
use function is_string;
use function str_replace;

final class Habu extends PluginBase {
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

    public const LOBBY_SCOREBOARD = 'lobby';
    public const QUEUE_SCOREBOARD = 'queue';
    public const MATCH_STARTING_SCOREBOARD = 'match-starting';
    public const MATCH_STARTING_PARTY_SCOREBOARD = 'match-starting-party';
    public const MATCH_PLAYING_SCOREBOARD = 'match-playing';
    public const MATCH_ENDING_SCOREBOARD = 'match-ending';

    private ?Config $messagesConfig = null;

    protected function onEnable(): void {
        self::setInstance($this);

//        $bootstrap = 'phar://' . $this->getServer()->getPluginPath() . $this->getName() . '.phar/vendor/autoload.php';
//        if (!is_file($bootstrap)) {
//            throw new RuntimeException('Could not find autoload.php in plugin phar, directory: ' . $bootstrap);
//        }
//
//        require_once $bootstrap;

        $this->saveDefaultConfig();
        $this->saveResource('scoreboard.yml', true);
        $this->saveResource('messages.yml', true);

        ScoreboardRegistry::getInstance()->load(new Config($this->getDataFolder() . 'scoreboard.yml'));
        ScoreboardRegistry::getInstance()->setScoreboardPlaceholders(new DefaultScoreboardPlaceholders());

        try {
            KitRegistry::getInstance()->loadAll();
        } catch (Exception $e) {
            throw new RuntimeException('Error loading kits: ' . $e->getMessage());
        }

        ArenaRegistry::getInstance()->loadAll();
        KnockbackRegistry::getInstance()->loadAll($this);

        // Default server listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerInteractListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerExhaustListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerJoinListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new BlockBreakListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new WorldSoundListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PlayerQuitListener(), $this);

        // Match listeners
        $this->getServer()->getPluginManager()->registerEvents(new PlayerKitAppliedListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new ProjectileLaunchListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityTeleportListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new SumoPlayerMoveListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityMotionListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new EntityDamageListener(), $this);

        // Party listeners
        $this->getServer()->getPluginManager()->registerEvents(new PartyCreateListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PartyDisbandListener(), $this);
        $this->getServer()->getPluginManager()->registerEvents(new PartyTransferListener(), $this);

        $this->getServer()->getCommandMap()->registerAll('bitrule', [
        	new JoinQueueCommand('joinqueue', 'Join a queue for a kit.', '/joinqueue <kit>'),
        	new LeaveQueueCommand('leavequeue', 'Leave from the queue.', '/leavequeue'),
        	new DurabilityCommand('durability'),
        	new KnockbackProfileCommand(),
        	new EventsMainCommand(),
        	new ArenaMainCommand()
        ]);

        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function (): void {
                DuelRegistry::getInstance()->tickStages();

                SumoEvent::getInstance()->update();
            }),
            20
        );

        $this->getScheduler()->scheduleRepeatingTask(
            new ClosureTask(function (): void {
                ProfileRegistry::getInstance()->tickScoreboard();
            }),
            5
        );

        $this->messagesConfig = new Config($this->getDataFolder() . 'messages.yml');
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
     * @return string
     */
    public static function prefix(): string {
        return TextFormat::GOLD . TextFormat::BOLD . 'Habu ' . TextFormat::GRAY . '» ' . TextFormat::RESET;
    }
}