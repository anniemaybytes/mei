<?php declare(strict_types=1);

set_error_handler(
    function ($errno) {
        die('Sorry, something went horribly wrong / PR Environment Error: ' . $errno);
    }
);
set_exception_handler(
    function (Throwable $e) {
        die('Sorry, something went horribly wrong / PR Environment Exception: ' . get_class($e));
    }
);

define('PUBLIC_ROOT', __DIR__);

require_once '../dispatch.php';
