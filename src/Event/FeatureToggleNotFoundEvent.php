<?php

namespace Unleash\Client\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Unleash\Client\Configuration\Context;

if (!class_exists(Event::class)) {
    require __DIR__ . '/../../stubs/event-dispatcher/Event.php';
}

final class FeatureToggleNotFoundEvent extends Event
{
    /**
     * @internal
     */
    public function __construct(
        private Context $context,
        private string $featureName,
    ) {
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
    public function getFeatureName(): string
    {
        return $this->featureName;
    }
}
