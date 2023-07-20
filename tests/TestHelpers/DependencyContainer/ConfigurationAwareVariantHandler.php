<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Unleash\Client\Configuration\Context;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
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

    public function getDefaultVariant(): Variant
    {
        return new DefaultVariant('test', false);
    }

    public function selectVariant(Feature $feature, Context $context): ?Variant
    {
        return null;
    }
}
