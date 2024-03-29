<?php

declare(strict_types=1);

const BASE_ROOT = __DIR__ . '/..';
const PUBLIC_ROOT = BASE_ROOT;
chdir(BASE_ROOT);

require_once BASE_ROOT . '/vendor/autoload.php';

$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['SERVER_NAME'] = 'mei.animebytes.local';
$_SERVER['SERVER_PORT'] = '7443';

DG\BypassFinals::enable(); // allows to mock final classes
