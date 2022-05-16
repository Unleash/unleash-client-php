<?php

namespace Unleash\Client\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

if (!class_exists(Event::class)) {
    require __DIR__ . '/../../stubs/event-dispatcher/Event.php';
}

final class FeatureToggleDisabledEvent extends Event
{
    /**
     * @internal
     */
    public function __construct(
        private readonly Feature $feature,
        private readonly Context $context,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFeature(): Feature
    {
        return $this->feature;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getContext(): Context
    {
        return $this->context;
    }
}
