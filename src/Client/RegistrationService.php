<?php

namespace Rikudou\Unleash\Client;

use Rikudou\Unleash\Strategy\StrategyHandler;

interface RegistrationService
{
    /**
     * @param iterable<StrategyHandler> $strategyHandlers
     */
    public function register(iterable $strategyHandlers): bool;
}
