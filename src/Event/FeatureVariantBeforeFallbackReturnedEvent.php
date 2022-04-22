<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;

final class FeatureVariantBeforeFallbackReturnedEvent
{
    /**
     * @internal
     */
    public function __construct(
        private Variant $fallbackVariant,
        private readonly ?Feature $feature,
        private readonly Context $context,
    ) {
    }

    public function getFallbackVariant(): Variant
    {
        return $this->fallbackVariant;
    }

    public function setFallbackVariant(Variant $fallbackVariant): void
    {
        $this->fallbackVariant = $fallbackVariant;
    }

    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    public function getContext(): Context
    {
        return $this->context;
    }
}
