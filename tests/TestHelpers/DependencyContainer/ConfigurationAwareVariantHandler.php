<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultProxyVariant;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\ProxyVariant;
use Unleash\Client\Helper\Builder\ConfigurationAware;
use Unleash\Client\Variant\VariantHandler;

final class ConfigurationAwareVariantHandler implements VariantHandler, ConfigurationAware
{
    /**
     * @var UnleashConfiguration|null
     */
    public $configuration = null;

    public function setConfiguration(UnleashConfiguration $configuration): void
    {
        $this->configuration = $configuration;
    }

    public function getDefaultVariant(): ProxyVariant
    {
        return new DefaultProxyVariant('test', false);
    }

    public function selectVariant(Feature $feature, Context $context): ?ProxyVariant
    {
        return null;
    }
}
