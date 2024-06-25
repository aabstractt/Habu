<?php

declare(strict_types=1);

namespace bitrule\practice\commands;

use bitrule\practice\form\duel\CommandDuelSelector;
use bitrule\practice\registry\DuelRegistry;
use bitrule\practice\TranslationKey;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\CommandException;
use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

final class DuelCommand extends Command {

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

        $duelInvite = DuelRegistry::getInstance()->getDuelInvites($target->getXuid())[$sender->getXuid()] ?? null;
        if ($duelInvite !== null && !$duelInvite->isExpired()) {
            $sender->sendMessage(TextFormat::RED . 'You have already sent a duel request to ' . $target->getName());

            return;
        }

        $form = new CommandDuelSelector(TextFormat::BLUE . 'Select a kit to duel ' . $target->getName());
        $form->setup($target);

        $sender->sendForm($form);
    }

    /**
     * @return self
     */
    public static function empty(): self {
        $command = new DuelCommand('duel', 'Send a duel invitation to a player', '/duel <player>');
        $command->setPermission('practice.command.duel');

        return $command;
    }
}