<?php

namespace Unleash\Client\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

if (!class_exists(Event::class)) {
    require __DIR__ . '/../../stubs/event-dispatcher/Event.php';
}

final class FeatureToggleMissingStrategyHandlerEvent extends Event
{
    /**
     * @readonly
     * @var \Unleash\Client\Configuration\Context
     */
    private $context;
    /**
     * @readonly
     * @var \Unleash\Client\DTO\Feature
     */
    private $feature;
    /**
     * @internal
     */
    public function __construct(Context $context, Feature $feature)
    {
        $this->context = $context;
        $this->feature = $feature;
    }
    /**
     * @codeCoverageIgnore
     */
    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @codeCoverageIgnore
     */
    public function getFeature(): Feature
    {
        return $this->feature;
    }
}
