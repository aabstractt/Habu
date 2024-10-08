<?php

declare(strict_types=1);

namespace bitrule\practice\arena\asyncio;

use bitrule\practice\Habu;
use Exception;
use pocketmine\scheduler\AsyncTask;
use RuntimeException;
use function is_callable;
use function microtime;

abstract class FileOperationTask extends AsyncTask {

    /** @var float|null */
    protected ?float $taskTime = null;
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
        if ($this->taskTime === null) {
            throw new RuntimeException('Task time is not set');
        }

        $this->taskTime = microtime(true) - $this->taskTime;

        if (!$this->success) return;

        try {
            if (!is_callable($closure = $this->fetchLocal('FILE_OPERATION_CLOSURE'))) return;

            $closure();
        } catch (Exception $e) {
            Habu::getInstance()->getLogger()->logException($e);
        }
    }
}