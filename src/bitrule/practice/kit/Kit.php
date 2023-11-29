<?php

declare(strict_types=1);

namespace bitrule\practice\kit;

final class Kit {

    public function __construct(
        private readonly string $name
    ) {}

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}