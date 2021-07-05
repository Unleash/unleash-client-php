<?php

/**
 * This file contains common definitions for the examples
 */

require_once __DIR__ . '/../vendor/autoload.php';

$appName = $gitlabEnvironment =
    getenv('UNLEASH_APP_NAME')
        ?: getenv('UNLEASH_GITLAB_ENVIRONMENT')
        ?: 'unleashSdkExamples';
$instanceId = getenv('UNLEASH_INSTANCE_ID') ?: $appName;
$appUrl = getenv('UNLEASH_APP_URL') ?: 'http://localhost:4242/api';
if (!getenv('UNLEASH_NO_API_KEY')) {
    $apiKey = getenv('UNLEASH_API_KEY') ?: trim(readline('Please input your api key: '));
}
