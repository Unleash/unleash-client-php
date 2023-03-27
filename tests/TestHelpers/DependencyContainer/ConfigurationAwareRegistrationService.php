<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Unleash\Client\Client\RegistrationService;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Helper\Builder\ConfigurationAware;

final class ConfigurationAwareRegistrationService implements RegistrationService, ConfigurationAware
{
    /**
     * @var UnleashConfiguration|null
     */
    public $configuration = null;

    public function setConfiguration(UnleashConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function register(iterable $strategyHandlers): bool
    {
        return false;
    }
}
