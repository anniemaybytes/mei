<?php /** @noinspection ForgottenDebugOutputInspection */

declare(strict_types=1);

set_error_handler(
    static function (int $errno, string $errstr, string $errfile, int $errline): void {
        error_log("$errstr ($errno) - $errfile:$errline");
        die('Sorry, something went horribly wrong / PR Environment Error: ' . $errno);
    },
    E_ERROR | E_PARSE
);

set_exception_handler(
    static function (Throwable $e): void {
        error_log((string)$e);
        die('Sorry, something went horribly wrong / PR Environment Exception: ' . get_class($e));
    }
);

define('PUBLIC_ROOT', __DIR__);

require_once '../dispatch.php';
