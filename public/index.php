<?php /** @noinspection ForgottenDebugOutputInspection */

declare(strict_types=1);

define('ERROR_REPORTING', E_ALL & ~(E_STRICT | E_NOTICE | E_WARNING | E_DEPRECATED));

set_error_handler(
    static function (int $errno, string $errstr, string $errfile, int $errline) {
        error_log("$errstr ($errno) - $errfile:$errline");
        die('Sorry, something went horribly wrong / PR Environment Error: ' . $errno);
    }
);

/** @phpstan-ignore-next-line */
set_exception_handler(
    static function (Throwable $e) {
        error_log((string)$e);
        die('Sorry, something went horribly wrong / PR Environment Exception: ' . get_class($e));
    }
);

define('PUBLIC_ROOT', __DIR__);

require_once '../dispatch.php';
