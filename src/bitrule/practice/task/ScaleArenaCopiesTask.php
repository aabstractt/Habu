<?php

declare(strict_types=1);

namespace bitrule\practice\task;

use Closure;
use pocketmine\scheduler\Task;

final class ScaleArenaCopiesTask extends Task {

    /** @var int */
    private int $progressCount = 0;

    /**
     * @param int      $amountExpected
     * @param Closure(int): void $progressClosure
     * @param Closure(): void $completionClosure
     */
    public function __construct(
        private readonly int      $amountExpected,
        private readonly Closure $progressClosure,
        private readonly Closure $completionClosure
    ) {}

    public function onRun(): void {
        if ($this->progressCount++ >= $this->amountExpected) {
            $this->getHandler()?->cancel();

            ($this->completionClosure)();
        }

        ($this->progressClosure)($this->progressCount);
    }
}