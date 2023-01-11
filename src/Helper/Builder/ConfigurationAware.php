<?php

namespace Unleash\Client\Helper\Builder;

use Unleash\Client\Configuration\UnleashConfiguration;

interface ConfigurationAware
{
    public function setConfiguration(UnleashConfiguration $configuration): void;
}
