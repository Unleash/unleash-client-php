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
     * @readonly
     */
    private Feature $feature;
    /**
     * @readonly
     */
    private Context $context;
    /**
     * @internal
     */
    public function __construct(Feature $feature, Context $context)
    {
        $this->feature = $feature;
        $this->context = $context;
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
