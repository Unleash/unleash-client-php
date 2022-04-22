<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

final class FeatureToggleDisabledEvent
{
    /**
     * @internal
     */
    public function __construct(
        private Feature $feature,
        private readonly Context $context,
    ) {
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function setFeature(Feature $feature): void
    {
        $this->feature = $feature;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
