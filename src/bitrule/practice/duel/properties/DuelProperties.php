<?php

declare(strict_types=1);

namespace bitrule\practice\duel\properties;

abstract class DuelProperties {
    // TODO: Duel properties bro

    /**
     * @param array $properties
     */
    public function __construct(protected array $properties = []) {}
}