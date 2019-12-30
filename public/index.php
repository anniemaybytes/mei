<?php declare(strict_types=1);

set_error_handler(
    function (int $errno, string $errstr, string $errfile, int $errline) {
        error_log("$errstr ($errno) - $errfile:$errline");
        die('Sorry, something went horribly wrong / PR Environment Error: ' . $errno);
    }
);
set_exception_handler(
    function (Throwable $e) {
        error_log((string)$e);
        die('Sorry, something went horribly wrong / PR Environment Exception: ' . get_class($e));
    }
);

define('PUBLIC_ROOT', __DIR__);

require_once '../dispatch.php';
