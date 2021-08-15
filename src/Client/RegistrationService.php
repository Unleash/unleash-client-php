<?php

namespace Unleash\Client\Client;

use Unleash\Client\Strategy\StrategyHandler;

interface RegistrationService
{
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function register(iterable $strategyHandlers): bool;
}
