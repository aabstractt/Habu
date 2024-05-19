<?php

declare(strict_types=1);

namespace bitrule\practice;

use InvalidArgumentException;
use pocketmine\utils\EnumTrait;
use function array_combine;
use function count;
use function str_replace;
use function ucwords;

/**
 * @method static self DUEL_END_STATISTICS_NORMAL()
 * @method static self DUEL_ELO_CHANGES_LOST()
 * @method static self DUEL_ELO_CHANGES_WIN()
 *
 * @method static self DUEL_END_STATISTICS_POT()
 * @method static self DUEL_OPPONENT_FOUND()
 * @method static self DUEL_WINNER_BROADCAST()
 *
 * @method static self BOXING_DUEL_HITS_DIFFERENCE_OPPONENT()
 * @method static self BOXING_DUEL_HITS_DIFFERENCE_SELF()
 * @method static self BOXING_DUEL_HITS_DIFFERENCE_NONE()
 * @method static self BOXING_DUEL_COMBO_OPPONENT()
 * @method static self BOXING_DUEL_COMBO_SELF()
 * @method static self BOXING_DUEL_COMBO_NONE()
 *
 * @method static self FIREBALL_FIGHT_PLAYER_DEAD_WITHOUT_KILLER()
 * @method static self FIREBALL_FIGHT_PLAYER_DEAD()
 * @method static self DUEL_PLAYER_DEAD_WITHOUT_KILLER()
 * @method static self DUEL_PLAYER_DEAD()
 *
 * @method static self PLAYER_JOINED_MESSAGE()
 * @method static self PLAYER_WELCOME_MESSAGE()
 * @method static self PLAYER_LEFT_MESSAGE()
 * @method static self PLAYER_QUEUE_JOINED()
 */
final class TranslationKey {
    use EnumTrait;

    /** @var string|null The key of the message. */
    private ?string $messageKey = null;
    /** @var array The arguments of the message. */
    private array $arguments = [];

    /**
     * Inserts default entries into the registry.
     *
     * (This ought to be private, but traits suck too much for that.)
     */
    protected static function setup(): void {
        self::registerAll(
            self::create(
                'DUEL_END_STATISTICS_NORMAL',
                'duel.end-statistics-normal',
                [
                	'opponent',
                	'self-elo-changes',
                	'self-critics',
                	'self-damage-dealt',
                	'opponent-critics',
                	'opponent-damage-dealt',
                ]
            ),
            self::create(
                'DUEL_ELO_CHANGES_LOST',
                'duel.elo-changes.lost',
                [
                	'amount'
                ]
            ),
            self::create(
                'DUEL_ELO_CHANGES_WIN',
                'duel.elo-changes.win',
                [
                	'amount'
                ]
            ),
            self::create(
                'DUEL_END_STATISTICS_POT',
                'duel.end-statistics-pot',
                [
                	'opponent',
                	'self-elo-changes',
                	'self-critics',
                	'self-damage-dealt',
                	'self-total-potions',
                	'opponent-critics',
                	'opponent-damage-dealt',
                	'opponent-total-potions'
                ]
            ),
            self::create(
                'DUEL_OPPONENT_FOUND',
                'duel.opponent-found',
                [
                	'player',
                	'type',
                	'kit'
                ]
            ),
            self::create(
                'DUEL_WINNER_BROADCAST',
                'duel.winner-broadcast',
                [
                	'winner',
                	'loser',
                	'kit'
                ]
            ),
            self::create(
                'BOXING_DUEL_HITS_DIFFERENCE_OPPONENT',
                'duel.boxing.hits-difference.opponent',
                [
                	'amount'
                ]
            ),
            self::create(
                'BOXING_DUEL_HITS_DIFFERENCE_SELF',
                'duel.boxing.hits-difference.self',
                [
                	'amount'
                ]
            ),
            self::create(
                'BOXING_DUEL_HITS_DIFFERENCE_NONE',
                'duel.boxing.hits-difference.none',
                []
            ),
            self::create(
                'BOXING_DUEL_COMBO_OPPONENT',
                'duel.boxing.current-combo.opponent',
                [
                	'amount'
                ]
            ),
            self::create(
                'BOXING_DUEL_COMBO_SELF',
                'duel.boxing.current-combo.self',
                [
                	'amount'
                ]
            ),
            self::create(
                'BOXING_DUEL_COMBO_NONE',
                'duel.boxing.current-combo.none',
                []
            ),
            self::create(
                'FIREBALL_FIGHT_PLAYER_DEAD_WITHOUT_KILLER',
                'duel.fireball-fight.player-dead-without-killer',
                [
                	'player'
                ]
            ),
            self::create(
                'FIREBALL_FIGHT_PLAYER_DEAD',
                'duel.fireball-fight.player-dead',
                [
                	'player',
                	'killer'
                ]
            ),
            self::create(
                'DUEL_PLAYER_DEAD_WITHOUT_KILLER',
                'duel.player-dead-without-killer',
                [
                	'player'
                ]
            ),
            self::create(
                'DUEL_PLAYER_DEAD',
                'duel.player-dead',
                [
                	'player',
                	'killer'
                ]
            ),
            self::create(
                'PLAYER_JOINED_MESSAGE',
                'player.joined-message',
                [
                	'player'
                ]
            ),
            self::create(
                'PLAYER_WELCOME_MESSAGE',
                'player.welcome-message',
                [
                	'player',
                	'online-players'
                ]
            ),
            self::create(
                'PLAYER_LEFT_MESSAGE',
                'player.left-message',
                [
                	'player'
                ]
            ),
            self::create(
                'PLAYER_QUEUE_JOINED',
                'player.queue-joined',
                [
                	'kit',
                	'type'
                ]
            )
        );
    }

    /**
     * @param mixed ...$arguments
     *
     * @return string
     */
    public function build(mixed...$arguments): string {
        if (count($arguments) !== count($this->arguments)) {
            throw new InvalidArgumentException('Invalid number of arguments. Expected ' . count($this->arguments) . ' but got ' . count($arguments) . '.');
        }

        if ($this->messageKey === null) {
            throw new InvalidArgumentException('The message key is not set.');
        }

        return Habu::wrapMessage($this->messageKey, array_combine($this->arguments, $arguments));
    }

    /**
     * @param string $enumName
     * @param string $messageKey
     * @param array  $arguments
     *
     * @return self
     */
    private static function create(string $enumName, string $messageKey, array $arguments): self {
        $self = new self($enumName);
        $self->messageKey = $messageKey;
        $self->arguments = $arguments;

        return $self;
    }

    /**
     * @param string $name
     *
     * @return string
     */
    public static function beautifulName(string $name): string {
        return ucwords(str_replace(['-', '_'], ' ', $name));
    }
}