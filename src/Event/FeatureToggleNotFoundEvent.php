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
     * @readonly
     * @var \Unleash\Client\Configuration\Context
     */
    private $context;
    /**
     * @readonly
     * @var string
     */
    private $featureName;
    /**
     * @internal
     */
    public function __construct(Context $context, string $featureName)
    {
        $this->context = $context;
        $this->featureName = $featureName;
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
