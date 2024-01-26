<?php

declare(strict_types=1);

namespace bitrule\practice\arena\impl;

use bitrule\practice\arena\AbstractArena;
use pocketmine\math\Vector3;
use RuntimeException;
use function array_merge;

/**
 * Class BridgeArena is a bridge arena.
 */
final class BridgeArena extends AbstractArena {

    /**
     * @param string   $name
     * @param Vector3  $firstPosition
     * @param Vector3  $secondPosition
     * @param Vector3  $firstPortal
     * @param Vector3  $secondPortal
     * @param string[] $duelTypes
     */
    public function __construct(
        string $name,
        Vector3 $firstPosition,
        Vector3 $secondPosition,
        private Vector3 $firstPortal,
        private Vector3 $secondPortal,
        array $duelTypes
    ) {
        parent::__construct($name, $firstPosition, $secondPosition, $duelTypes);
    }

    /**
     * @return Vector3
     */
    public function getFirstPortal(): Vector3 {
        return $this->firstPortal;
    }

    /**
     * @param Vector3 $firstPortal
     */
    public function setFirstPortal(Vector3 $firstPortal): void {
        $this->firstPortal = $firstPortal;
    }

    /**
     * @return Vector3
     */
    public function getSecondPortal(): Vector3 {
        return $this->secondPortal;
    }

    /**
     * @param Vector3 $secondPortal
     */
    public function setSecondPortal(Vector3 $secondPortal): void {
        $this->secondPortal = $secondPortal;
    }

    /**
     * @param string $duelType
     */
    public function addDuelType(string $duelType): void {
        throw new RuntimeException('This arena type cannot have duel types.');
    }

    /**
     * @return array<string, mixed>
     */
    public function serialize(): array {
        return array_merge(
            parent::serialize(),
            [
            	'type' => 'bridge',
            	'first_portal' => self::serializeVector($this->firstPortal),
            	'second_portal' => self::serializeVector($this->secondPortal)
            ]
        );
    }

    /**
     * @param string $name
     * @param array  $data
     *
     * @return BridgeArena
     */
    public static function parse(string $name, array $data): BridgeArena {
        return new BridgeArena(
            $name,
            self::deserializeVector($data['first_position'] ?? []),
            self::deserializeVector($data['second_position'] ?? []),
            self::deserializeVector($data['first_portal'] ?? []),
            self::deserializeVector($data['second_portal'] ?? []),
            $data['kits'] ?? []
        );
    }

    /**
     * @param string $name
     *
     * @return BridgeArena
     */
    protected static function parseEmpty(string $name): BridgeArena {
        return new BridgeArena(
            $name,
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero(),
            Vector3::zero(),
            []
        );
    }
}