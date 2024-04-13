<?php

declare(strict_types=1);

namespace bitrule\practice\arena\asyncio;

use bitrule\practice\Practice;
use Exception;
use pocketmine\scheduler\AsyncTask;
use function is_callable;
use function microtime;

abstract class FileOperationTask extends AsyncTask {

    /** @var float */
    protected float $taskTime;
    /** @var bool */
    protected bool $success = false;

    /**
     * @param string        $source
     * @param callable(): void|null $closure
     */
    public function __construct(
        protected string $source,
        ?callable $closure = null
    ) {
        $this->storeLocal('FILE_OPERATION_CLOSURE', $closure);
    }

    /**
     * Actions to execute when run
     *
     * @return void
     */
    public function onRun(): void {
        $this->taskTime = microtime(true);
    }

    public function onCompletion(): void {
        $this->taskTime = microtime(true) - $this->taskTime;

        if (!$this->success) return;

        try {
            if (!is_callable($closure = $this->fetchLocal('FILE_OPERATION_CLOSURE'))) return;

            $closure();
        } catch (Exception $e) {
            Practice::getInstance()->getLogger()->logException($e);
        }
    }
}