#!/usr/bin/env php
<?php
/**
 * XMLIterator build script
 */

$errors = 0;
$warnings = 0;

$buildDir = __DIR__ . '/build';
$concatenateDir = $buildDir . '/include';
$concatenateFile = $concatenateDir . '/xmlreader-iterators.php';
$autoLoadFile = __DIR__ . '/autoload.php';

### test if autoload.php contains all classes ###
build_test_autoload_file($errors, $autoLoadFile);

### clean ###
build_make_clean($errors, $buildDir, $concatenateDir);

### create concatenateFile ###
build_create_concatenate_file($errors, $concatenateFile, $autoLoadFile);
copy_to_dir('README.md', $concatenateDir);

if ($errors) {
    printf("ERROR: Build had %d errors.\n");
}


/**
 * @param $errors
 * @param $autoLoadFile
 */
function build_test_autoload_file(&$errors, $autoLoadFile)
{
    require_once($autoLoadFile);

    foreach (glob('src/*.php') as $file) {
        $class = basename($file, '.php');


        if (!class_exists($class) && !interface_exists($class)) {
            echo "ERROR: ", $class, " does not exists.\n";
            $errors++;
        }
    }
}

/**
 * @param $errors
 * @param $concatenateFile
 * @param $autoLoadFile
 *
 * @internal param $buildDir
 * @internal param $concatenateFileHandle
 */
function build_create_concatenate_file(&$errors, $concatenateFile, $autoLoadFile)
{
    if (!is_dir(dirname($concatenateFile))) {
        echo "ERROR: target dir '", dirname($concatenateFile), "' missing.\n";
        $errors++;

        return;
    } else {
        $concatenateFileHandle = fopen($concatenateFile, 'a');
        if (!$concatenateFileHandle) {
            echo "ERROR: concatenateFile '$concatenateFile' can not be created.\n";
            $errors++;

            return;
        }
    }

    ### write concatenateFile based on autoload.php ###
    $pattern = '~^require .*\'/([^.]*\.php)\';$~';
    $lines   = preg_grep($pattern, file($autoLoadFile));
    if (!$lines) {
        echo "ERROR: Problem parsing file.\n";
    }
    $count = 0;
    foreach ($lines as $line) {
        $result = preg_match($pattern, $line, $matches);
        if (!$result) {
            echo "ERROR: Problem parsing file.\n";
            continue;
        }
        $file   = sprintf('src/%s', $matches[1]);
        $handle = fopen($file, 'r');

        if (!$handle) {
            echo "ERROR: Can not open file '$file' for reading.\n";
            continue;
        }

        if (!isset($concatenateFileHandle)) {
            fclose($handle);
            continue;
        }

        if ($count !== 0 && false === fseek_first_empty_line($handle)) { // first file is complete copy
            echo "ERROR: Problem reading file until first empty line.\n";
            continue;
        }

        stream_copy_to_stream($handle, $concatenateFileHandle);
        fclose($handle);
        $count++;
    }

    fclose($concatenateFileHandle);
    printf("INFO: concatenated %d files into %s.\n", $count, cwdname($concatenateFile));
}

/**
 * @param $errors
 * @param $buildDir
 * @param $concatenateDir
 */
function build_make_clean(&$errors, $buildDir, $concatenateDir)
{
    if (is_dir($buildDir)) {
        deltree($buildDir);
    }
    if (is_dir($buildDir)) {
        printf("ERROR: cannot clean buildDir %s .\n", cwdname($buildDir));
        $errors++;
    } else {
        mkdir($buildDir);
        mkdir($concatenateDir);
    }
}


/**
 * @param $handle
 *
 * @return bool
 */
function fseek_first_empty_line($handle)
{
    $lastLine = 0;
    while (false !== $line = fgets($handle)) {
        if ('' === rtrim($line, "\r\n")) {
            break;
        }
        $lastLine += strlen($line);
    }
    if ($line === false) {
        return false;
    }

    return !fseek($handle, $lastLine);
}


/**
 * shorten pathname realtive to cwd
 *
 * @param $path
 *
 * @return string
 */
function cwdname($path)
{
    static $base;
    $base || $base = realpath('.');
    $result = realpath($path);

    if (substr($result, 0, strlen($base)) === $base) {
        $result = '.' . substr($result, strlen($base));
    } else {
        echo "INFO: File '$path' not relative to cwd. Please verify.\n";
    }

    return strtr($result, '\\', '/');
}


/**
 * @param string $file
 * @param string $dir
 *
 * @return bool
 */
function copy_to_dir($file, $dir)
{
    $target = rtrim($dir, '/\\') . '/' . basename($file);
    if (realpath($file) === realpath($target)) {
        echo "INFO: source and target in copy_to_dir() are the same.\n";

        return true; // already copied
    }
    $result = copy($file, $target);

    if ($result) {
        printf("INFO: copied %s to %s.\n", cwdname($file), cwdname($target));
    }

    return $result;
}

/**
 * deltree()  - delete a directory incl. subdirectories and files therein.
 *
 * implemented as a stack so that no recursion is necessar and
 * traversal is fast.
 *
 * @param $path
 */
function deltree($path)
{
    if (!is_dir($path) || is_link($path)) {
        echo "ERROR: given path rejected by deltree.\n";

        return;
    }

    $stack      = array($path);
    $rmdirStack = array();
    while ($stack) {
        $path = array_pop($stack);
        $it   = new DirectoryIterator($path);
        foreach ($it as $file) {
            /* @var $file DirectoryIterator */
            if ($file->isDot()) {
                continue;
            }
            $localPath = $path . '/' . $file->getBasename();
            if ($file->isDir()) {
                $stack[] = $localPath;
            } elseif ($file->isLink() || $file->isFile()) {
                $result = unlink($localPath);
                if (!$result) {
                    echo "ERROR: Failed to delete file '$localPath'. Expecting more problems.\n";
                }
            } else {
                printf(
                    "ERROR: Unknown processing for %s [%s] (%s) isDot: %d\n",
                    $file,
                    get_class($file),
                    $localPath,
                    $file->isDir()
                );
            }
        }
        unset($file, $it);
        array_unshift($rmdirStack, $path);
    }

    clearstatcache(true);
    foreach ($rmdirStack as $path) {
        chmod($path, 0777);
        clearstatcache(true, $path);
        $result = @rmdir($path);
        if (!$result) {
            echo "ERROR: Failed to delete directory '$path'. Skipping rest.\n";
            break;
        }
    }
}
