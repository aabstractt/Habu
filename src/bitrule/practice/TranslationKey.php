<?php

declare(strict_types=1);

namespace bitrule\practice;

use InvalidArgumentException;
use pocketmine\utils\EnumTrait;
use function array_combine;
use function count;

/**
 * @method static self DUEL_END_STATISTICS_NORMAL()
 * @method static self DUEL_END_STATISTICS_POT()
 * @method static self DUEL_OPPONENT_FOUND()
 *
 * @method static self BOXING_DUEL_HITS_DIFFERENCE_OPPONENT()
 * @method static self BOXING_DUEL_HITS_DIFFERENCE_SELF()
 * @method static self BOXING_DUEL_HITS_DIFFERENCE_NONE()
 * @method static self BOXING_DUEL_COMBO_OPPONENT()
 * @method static self BOXING_DUEL_COMBO_SELF()
 * @method static self BOXING_DUEL_COMBO_NONE()
 */
final class TranslationKey {
    use EnumTrait;

    /** @var string|null The key of the message. */
    private ?string $messageKey;
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
                'DUEL_END_STATISTICS_POT',
                'match.end-statistics-pot',
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
                'match.opponent-found',
                [
                	'player',
                	'type',
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
                [
                	'amount'
                ]
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
                [
                	'amount'
                ]
            )
        );
    }

    /**
     * @param string ...$arguments
     *
     * @return string
     */
    public function build(string...$arguments): string {
        if (count($arguments) !== count($this->arguments)) {
            throw new InvalidArgumentException('Invalid number of arguments. Expected ' . count($this->arguments) . ' but got ' . count($arguments) . '.');
        }

        if ($this->messageKey === null) {
            throw new InvalidArgumentException('The message key is not set.');
        }

        return Practice::wrapMessage($this->messageKey, array_combine($this->arguments, $arguments));
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
}