<?php

namespace Gladtest;

const GLAD_ROOT = __DIR__ . '/..';

// Local config
require_once __DIR__ . '/../config.local.php';

error_reporting(-1);

ini_set('date.timezone', 'Pacific/Auckland');

// Catch errors as exceptions
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
});

// Composer autoload
require_once __DIR__ . '/../vendor/autoload.php';
