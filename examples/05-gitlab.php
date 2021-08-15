<?php

use Unleash\Client\UnleashBuilder;

putenv('UNLEASH_NO_API_KEY=1');
require __DIR__ . '/_common.php';

// make use of the specialized builder 'createForGitlab()' which disables metrics and registration
$unleash = UnleashBuilder::createForGitlab()
    ->withGitlabEnvironment($gitlabEnvironment)
    ->withAppUrl($appUrl)
    ->withInstanceId($instanceId)
    ->build();

var_dump($unleash->isEnabled('my_feature'));
