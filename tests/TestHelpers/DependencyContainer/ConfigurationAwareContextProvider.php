<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\ContextProvider\UnleashContextProvider;
use Unleash\Client\Helper\Builder\ConfigurationAware;

final class ConfigurationAwareContextProvider implements UnleashContextProvider, ConfigurationAware
{
    public function setConfiguration(UnleashConfiguration $configuration): void
    {
        // this method shouldn't ever be called because this class is a cyclic dependency
    }

    public function getContext(): Context
    {
        return new UnleashContext();
    }
}
