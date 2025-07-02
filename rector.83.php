<?php

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\DowngradeLevelSetList;

return static function (RectorConfig $config): void {
    $config->sets([
        DowngradeLevelSetList::DOWN_TO_PHP_83,
    ]);
};
