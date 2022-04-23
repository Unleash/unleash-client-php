<?php

namespace Unleash\Client\Event;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;
use Unleash\Client\Strategy\StrategyHandler;

final class FeatureToggleNoStrategyHandlerEvent
{
    private ?StrategyHandler $strategyHandler = null;

    /**
     * @internal
     */
    public function __construct(
        private readonly Context $context,
        private readonly Feature $feature,
    ) {
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    public function getFeature(): Feature
    {
        return $this->feature;
    }

    public function getStrategyHandler(): ?StrategyHandler
    {
        return $this->strategyHandler;
    }

    public function setStrategyHandler(?StrategyHandler $strategyHandler): void
    {
        $this->strategyHandler = $strategyHandler;
    }
}
