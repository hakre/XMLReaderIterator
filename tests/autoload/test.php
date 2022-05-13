<?php
/*
 * This file is part of the XMLReaderIterator package.
 *
 * Copyright (C) 2012, 2013, 2014, 2022 hakre <http://hakre.wordpress.com>
 *
 * test.php - autoload.php test script
 *
 * usage: php tests/autoload/test.php --require autoload.php
 *        php tests/autoload/test.php --require vendor/autoload.php
 *
 * given the require file, the following tests need to pass:
 *
 * 1. the require-file can be required
 * 2. require returns as expected
 * 3. classes/interfaces exist (autoload or included)
 */

// emit/log errors on stderr only (the default CLI SAPI error logger)
if (function_exists('ini_set')) {
    ini_set('log_errors', '1');
    ini_set('error_log', '');
    ini_set('display_errors', '0');
    ini_set('error_reporting', (string)~0);
}

$help = false;
$options = getopt('h', array('help', 'require:'));
if (false === $options) {
    fprintf(STDERR, "fatal: failed to parse command-line options.\n");
} else {
    $help = isset($options['h']) || isset($options['help']);
    $require = isset($options['require']) ? realpath($options['require']) : null;
    if (empty($require) || !file_exists($require)) {
        fprintf(STDERR, "fatal: --require: not a file: \"%s\".\n", $require);
        unset($require);
        $help = false;
    }
}

if (!isset($require) || $help) {
    fprintf(STDOUT, "usage: %s --require <file>\n", basename(@$argv[0] ?: __FILE__));
    exit($help ? 0 : 1);
}

$label = $require;
$cwd = getcwd();
if (false !== $cwd) {
    $base = realpath($cwd);
    if (0 === strpos($require, $base . DIRECTORY_SEPARATOR)) {
        $label = substr($require, strlen($base) + 1);
    }
}
unset($cwd, $base);

// acquire project class-names by glob pattern
$requiredClassNames = array_reduce(glob('src/*.php'), function (array $c, $p) {
    $c[] = basename($p, '.php'); return $c;
}, array());
if (empty($requiredClassNames)) {
    fprintf(STDERR, "fatal: no classes to check.\n");
    exit(1);
}

// error handling incl. on shutdown to highlight last error if not yet reported
$phpErrors = (object)array('total' => 0, 'by_number' => array(), 'errors' => array(), 'report_on_shutdown' => true);
function error_handler($no, $str, $file, $line)
{
    global $phpErrors;
    $phpErrors->total++;
    $error = array('type' => $no, 'message' => $str, 'file' => $file, 'line' => $line);
    $phpErrors->by_number[$no][] = $error;
    $phpErrors->errors[] = $error;
    return false;
}
function shutdown_function()
{
    global $phpErrors, $label;
    if (!$phpErrors->report_on_shutdown) {
        return;
    }
    $last = error_get_last();
    if (null === $last) {
        return;
    }
    $lastReported = end($phpErrors->errors);
    if ($lastReported === $last) {
        return;
    }
    fprintf(STDERR, "fatal: php %s with \"%s\" [%d] %s in %s on line %s.\n", PHP_VERSION, $label, $last['type'], $last['message'], $last['file'], $last['line']);
}
set_error_handler('error_handler');
register_shutdown_function('shutdown_function');

/*
 * test: 1. the require-file can be required
 */

$requireResult = require_once($require);
$phpErrors->report_on_shutdown = false;
if ($phpErrors->total) {
    fprintf(STDERR, "fatal: php %s with \"%s\" %d type(s) of error(s) ([%s]), %d total error(s) for require of file: \"%s\".\n", PHP_VERSION, $label, count($phpErrors->by_number), implode('], [', array_keys($phpErrors->by_number)), $phpErrors->total, $require);
    exit(1);
}

/*
 * test: 2. require returns as expected
 */

$class = 'Composer\Autoload\ClassLoader';
if ($requireResult !== 1 && !(is_object($requireResult) && get_class($requireResult) === $class)) {
    fprintf(STDERR, "fatal: not a valid require file: \"%s\".\n", $require);
    exit(1);
}
unset($requireResult, $class);

/*
 * test: 3. classes/interfaces exist (autoload or included)
 */

$errors = $classes = 0;
foreach ($requiredClassNames as $class) {
    $classes++;
    if (!class_exists($class) && !interface_exists($class)) {
        fprintf(STDERR, "error: class or interface #%d \"%s\" does not exist.\n", $classes, $class);
        $errors++;
    }
}
$errors += $phpErrors->total;

fprintf(STDOUT, "autoload test: php %s with \"%s\" for %d classes: %s\n", PHP_VERSION, $label, $classes, $errors ? sprintf('%d error(s).', $errors) : '[OK]');

exit($errors ? 1 : 0);
