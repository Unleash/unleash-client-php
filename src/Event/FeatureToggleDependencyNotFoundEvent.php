<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;

final class FeatureToggleDependencyNotFoundEvent extends AbstractEvent
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Context $context,
        private readonly string $featureName,
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getFeatureName(): string
    {
        return $this->featureName;
    }
}
