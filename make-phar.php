<?php

declare(strict_types=1);

use pocketmine\utils\Git;
use pocketmine\utils\Terminal;

require_once("vendor\pocketmine\pocketmine-mp\src\utils\Git.php");
require_once("vendor\pocketmine\pocketmine-mp\src\utils\Process.php");
require_once("vendor\pocketmine\pocketmine-mp\src\utils\Terminal.php");
require_once("vendor\pocketmine\pocketmine-mp\src\utils\Utils.php");

/**
 * @return Generator
 */
function main(): Generator {
    $start = microtime(true);

    $opts = getopt("", ['out:', 'release']);
    $targetPath = $opts['out'] ?? getcwd();

    if (!is_string($basePath = getcwd()) || !is_string($targetPath)) {
        echo 'Invalid directory';

        return;
    }

    $gitHash = Git::getRepositoryStatePretty($basePath);

    if ($gitHash === str_repeat("00", 20)) {
        $gitHash = null;
    }

    $basePath .= DIRECTORY_SEPARATOR;
    $targetPath .= DIRECTORY_SEPARATOR;

    $array = readAndUpdatePluginYml($basePath, isset($opts['release']));

    $pharName = $array['name'] . '.phar';

    if (file_exists($targetPath . $pharName)) {
        yield 'Phar file already exists, overwriting...';

        try{
            Phar::unlinkArchive($targetPath . $pharName);
        }catch(PharException){
            //unlinkArchive() doesn't like dodgy phars
            unlink($targetPath . $pharName);
        }
    }

    yield 'Adding files...';

    $files = [];

    $exclusions = ['.idea', '.gitignore', 'composer.json', 'composer.lock', 'make-phar.php', '.git', 'vendor', 'composer.phar', $pharName];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($basePath)) as $path => $file) {
        $bool = true;
        foreach ($exclusions as $exclusion) {
            if (str_contains($path, $exclusion)) {
                $bool = false;

                break;
            }
        }

        if (!$bool || !$file->isFile()) {
            continue;
        }

        if (!is_string($string = str_replace($basePath, "", $path))) {
            continue;
        }

        yield 'Adding ' . $string;

        $files[$string] = $path;
    }

    yield 'Compressing...' . PHP_EOL;

    $phar = new Phar($targetPath . $pharName);
    $phar->startBuffering();
    $phar->setSignatureAlgorithm(Phar::SHA1);

    $array = readAndUpdatePluginYml($basePath, isset($opts['release']));

    if ($gitHash !== null) {
        yield 'Git hash detected as ' . $gitHash . PHP_EOL;

        $array['git'] = $gitHash;
    }

    $phar->setMetadata($array);

    yield '------------------------------------------------';
    yield Terminal::$COLOR_GREEN . 'BUILD SUCCESS';
    yield '------------------------------------------------';

    $count = count($phar->buildFromIterator(new ArrayIterator($files)));

    yield 'Added ' . $count . ' files';

    $phar->compressFiles(Phar::GZ);
    $phar->stopBuffering();

    yield 'Done in ' . round(microtime(true) - $start, 1) . 's';
}

/**
 * @param string $ymlPath
 * @param bool   $updateVersion
 *
 * @return array
 */
function readAndUpdatePluginYml(string $ymlPath, bool $updateVersion): array {
    $array = file_exists($ymlPath . 'plugin.yml') ? (is_array($array = yaml_parse_file($ymlPath . 'plugin.yml')) ? $array : []) : [];

    if (count($array) === 0) {
        return [];
    }

    $matches = array_map("intval", explode(".", $array['version']));
    if(count($matches) < 2){
        throw new InvalidArgumentException("Invalid version '" . $array['version'] . "', should contain at least 2 version digits");
    }

    if ($updateVersion) {
        if ($matches[1] === 9) {
            $matches[0]++;
            $matches[1] = 0;
        } else {
            $matches[1]++;
        }
    }

    $array['version'] = sprintf('%s.%s', $matches[0], $matches[1]);

    yaml_emit_file($ymlPath . 'plugin.yml', $array);

    return $array;
}

Terminal::init(true);

foreach (main() as $line) {
    echo Terminal::$COLOR_GRAY . '[' . Terminal::$COLOR_BLUE . 'INFO' . Terminal::$COLOR_GRAY . '] ' . Terminal::$COLOR_WHITE . $line . Terminal::$FORMAT_RESET . PHP_EOL;
}