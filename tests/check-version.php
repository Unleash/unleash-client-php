<?php

use Unleash\Client\Unleash;

if (!isset($argv[1])) {
    echo 'No argument with version provided', PHP_EOL;
    exit(1);
}

require_once __DIR__ . '/../src/Unleash.php';

if (Unleash::SDK_VERSION !== $argv[1]) {
    echo sprintf(
        "The version provided is '%s', the SDK is set to version '%s'",
        $argv[1],
        Unleash::SDK_VERSION
    ), PHP_EOL;
    exit(2);
}
