<?php

declare(strict_types=1);

namespace bitrule\practice\arena\asyncio;

use pocketmine\Server;
use RuntimeException;
use function closedir;
use function copy;
use function is_dir;
use function mkdir;
use function opendir;
use function readdir;
use function sprintf;

final class FileCopyAsyncTask extends FileOperationTask {

    /**
     * @param string        $source
     * @param string        $destination
     * @param callable(): void|null $closure
     */
    public function __construct(string $source, private string $destination, ?callable $closure = null) {
        parent::__construct($source, $closure);
    }

    public function onRun(): void {
        parent::onRun();

        $this->success = self::recurse_copy($this->source, $this->destination);
    }

    public function onCompletion(): void {
        parent::onCompletion();

        Server::getInstance()->getLogger()->error(sprintf(($this->success ? 'Copied' : 'Unable to copy') . ' file "%s" to "%s"', $this->source, $this->destination));
    }

    /**
     * @param string $src
     * @param string $dst
     * @return bool
     */
    public static function recurse_copy(string $src, string $dst): bool {
        $dir = opendir($src);

        if ($dir === false) {
            return false;
        }

        if (is_dir($dst)) {
            FileDeleteAsyncTask::recurse_delete($dst);
        }

        if (!@mkdir($dst, 0777, true) && !is_dir($dst)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dst));
        }

        while (($file = readdir($dir)) !== false) {
            if ($file != '.' && $file != '..') {
                if (is_dir($src . '/' . $file)) {
                    self::recurse_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }

        closedir($dir);

        return true;
    }
}