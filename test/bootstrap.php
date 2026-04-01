<?php

error_reporting(E_ALL & ~E_DEPRECATED);

spl_autoload_register(function ($class) {
    $prefix = 'Barberry\\Plugin\\Csv\\';
    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $path = __DIR__ . '/../src/Barberry/Plugin/Csv/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require_once $path;
    }
});

require_once __DIR__ . '/Support/BarberryStubs.php';
require_once __DIR__ . '/Support/TestCase.php';
