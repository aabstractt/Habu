<?php

declare(strict_types=1);

namespace bitrule\practice\arena\asyncio;

use pocketmine\Server;
use function glob;
use function is_bool;
use function is_dir;
use function rmdir;
use function str_ends_with;
use function unlink;

final class FileDeleteAsyncTask extends FileOperationTask {

    public function onRun(): void {
        parent::onRun();

        $this->success = self::recurse_delete($this->source);
    }

    /**
     * @param string $src
     * @return bool
     */
    public static function recurse_delete(string $src): bool {
        if (!is_dir($src)) {
            return false;
        }

        if (!str_ends_with($src, '/')) {
            $src .= '/';
        }

        if (is_bool($files = glob($src . '*', GLOB_MARK))) return false;

        foreach ($files as $file) {
            if (is_dir($file)) {
                self::recurse_delete($file);
            } else {
                @unlink($file);
            }
        }

        @rmdir($src);

        return true;
    }

    public function onCompletion(): void {
        parent::onCompletion();

        if ($this->success) {
            Server::getInstance()->getLogger()->info('Deleted file ' . $this->source);
        } else {
            Server::getInstance()->getLogger()->error('Unable to delete file ' . $this->source);
        }
    }
}