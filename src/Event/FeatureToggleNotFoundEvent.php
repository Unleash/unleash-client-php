<?php

namespace Unleash\Client\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

final class FeatureToggleNotFoundEvent extends Event
{
    private ?Feature $feature = null;

    private ?bool $enabled = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly Context $context,
        private readonly string $featureName,
    ) {
    }

    public function getFeature(): ?Feature
    {
        return $this->feature;
    }

    public function setFeature(?Feature $feature): void
    {
        $this->feature = $feature;
    }

    public function isEnabled(): ?bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
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
