<?php

declare(strict_types=1);

namespace bitrule\practice;

use bitrule\practice\commands\ArenaMainCommand;
use bitrule\practice\commands\DurabilityCommand;
use bitrule\practice\commands\JoinQueueCommand;
use bitrule\practice\commands\KnockbackProfileCommand;
use bitrule\practice\commands\LeaveQueueCommand;
use bitrule\practice\duel\stage\StageScoreboard;
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
use bitrule\practice\profile\Profile;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KitRegistry;
use bitrule\practice\registry\KnockbackRegistry;
use bitrule\practice\registry\ProfileRegistry;
use bitrule\practice\registry\QueueRegistry;
use bitrule\scoreboard\Scoreboard;
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
use function is_string;
use function str_replace;
use function str_starts_with;
use function time;

final class Habu extends PluginBase {
    use SingletonTrait {
        setInstance as private;
        reset as private;
    }

    /** @var array<string, array<string, string>> */
    private array $scoreboardLines = [];

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

        $config = new Config($this->getDataFolder() . 'scoreboard.yml');
        if (!is_array($scoreboardLine = $config->get('lines'))) {
            throw new RuntimeException('Invalid scoreboard.yml');
        }

        $this->scoreboardLines = $scoreboardLine;

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
        	new ArenaMainCommand(),
        	new JoinQueueCommand('joinqueue', 'Join a queue for a kit.', '/joinqueue <kit>'),
        	new LeaveQueueCommand('leavequeue', 'Leave from the queue.', '/leavequeue'),
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
     * @param Player $player
     * @param string $identifier
     */
    public static function applyScoreboard(Player $player, string $identifier): void {
        $profile = ProfileRegistry::getInstance()->getProfile($player->getXuid());
        if ($profile === null) {
            throw new RuntimeException('Local profile not found for player: ' . $player->getName());
        }

        if (($scoreboard = $profile->getScoreboard()) !== null) {
            $scoreboard->hide($player); // TODO: Yes ??????
        }

        $profile->setScoreboard($scoreboard = new Scoreboard());

        // TODO: Please make this more clean :sad:
        $scoreboard->load(self::getInstance()->scoreboardLines[$identifier] ?? throw new RuntimeException('Scoreboard not found: ' . $identifier));
        $scoreboard->show($player);
        $scoreboard->update($player, $profile);
    }

    /**
     * Replace placeholders in the text.
     *
     * @param Player  $player
     * @param Profile $profile
     * @param string  $identifier
     *
     * @return string|null
     */
    public static function replacePlaceholders(Player $player, Profile $profile, string $identifier): ?string {
        if ($identifier === 'total-queue-count') return (string) (QueueRegistry::getInstance()->getQueueCount());
        if ($identifier === 'total-duel-count') return (string) (DuelRegistry::getInstance()->getDuelsCount());
        if ($identifier === 'online-players') return (string) (count(self::getInstance()->getServer()->getOnlinePlayers()));

        if (str_starts_with($identifier, 'queue-')) {
            if (($queue = $profile->getQueue()) === null) return null;

            if ($identifier === 'queue-type') return $queue->isRanked() ? 'Ranked' : 'Unranked';
            if ($identifier === 'queue-kit') return $queue->getKitName();
            if ($identifier === 'queue-duration') return gmdate('i:s', time() - $queue->getTimestamp());
        }

        $duel = DuelRegistry::getInstance()->getDuelByPlayer($player->getXuid());
        if ($duel === null) return null;

        $result = $duel->replacePlaceholders($player, $identifier);
        if ($result !== null) return $result;

        $stage = $duel->getStage();
        if ($stage instanceof StageScoreboard) return $stage->replacePlaceholders($duel, $player, $profile, $identifier);

        return null;
    }

    /**
     * @return string
     */
    public static function prefix(): string {
        return TextFormat::GOLD . TextFormat::BOLD . 'Habu ' . TextFormat::GRAY . '» ' . TextFormat::RESET;
    }
}