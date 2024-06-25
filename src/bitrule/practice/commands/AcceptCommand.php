<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\duel\impl\NormalDuelImpl;
use bitrule\practice\registry\ArenaRegistry;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\registry\KitRegistry;
use Exception;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Ramsey\Uuid\Uuid;
use RuntimeException;

final class AcceptCommand extends Command {

    /**
     * @param CommandSender $sender
     * @param string        $commandLabel
     * @param array         $args
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): void {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . 'You must be a player to use this command');

            return;
        }

        if (count($args) < 1) {
            $sender->sendMessage(TextFormat::RED . 'Usage: /duel <player>');

            return;
        }

        if ($sender->getWorld() !== Server::getInstance()->getWorldManager()->getDefaultWorld()) {
            $sender->sendMessage(TextFormat::RED . 'You can only do this in the lobby');

            return;
        }

        $target = Server::getInstance()->getPlayerByPrefix($args[0]);
        if ($target === null) {
            $sender->sendMessage(TextFormat::RED . 'Player not found');

            return;
        }

        if ($target->getXuid() === $sender->getXuid()) {
            $sender->sendMessage(TextFormat::RED . 'You cannot duel yourself');

            return;
        }

        $duelInvite = DuelRegistry::getInstance()->getDuelInvites($sender->getXuid())[$target->getXuid()] ?? null;
        if ($duelInvite === null || $duelInvite->isExpired()) {
            $sender->sendMessage(TextFormat::RED . 'You have not received a duel request from ' . $target->getName());

            return;
        }

        $kit = KitRegistry::getInstance()->getKit($duelInvite->getKitName());
        if ($kit === null) {
            $sender->sendMessage(TextFormat::RED . 'The kit ' . $duelInvite->getKitName() . ' does not exist');

            return;
        }

        $target->sendMessage(TextFormat::GREEN . $sender->getName() . ' has accepted your duel request');

        $totalPlayers = [$sender, $target];

        try {
            $arenaProperties = ArenaRegistry::getInstance()->getRandomArena($kit);
            if ($arenaProperties === null) {
                throw new RuntimeException('No arenas available for duel type: ' . $kit->getName());
            }

            DuelRegistry::getInstance()->prepareDuel(
                $totalPlayers,
                new NormalDuelImpl(
                    $arenaProperties,
                    $kit,
                    Uuid::uuid4()->toString(),
                    true
                )
            );
        } catch (Exception $e) {
            foreach ($totalPlayers as $player) {
                $player->sendMessage(TextFormat::RED . 'Something went wrong while creating the duel.');
                $player->sendMessage(TextFormat::RED . $e->getMessage());
            }
        }
    }

    /**
     * Create an instance of the AcceptCommand class with predefined parameters.
     *
     * This method is used to create a new instance of the AcceptCommand class with predefined parameters.
     * The command name is set to 'accept', the description is set to 'Accept a duel invitation', and the usage message is set to '/accept <player>'.
     * The permission for this command is set to 'practice.command.accept'.
     *
     * @return self Returns the newly created instance of the AcceptCommand class.
     */
    public static function empty(): self {
        $command = new AcceptCommand('accept', 'Accept a duel invitation', '/accept <player>');
        $command->setPermission('practice.command.accept');

        return $command;
    }
}