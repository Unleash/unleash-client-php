<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\DTO\Strategy;

abstract class AbstractStrategyHandler implements StrategyHandler
{
    public function supports(Strategy $strategy): bool
    {
        return $strategy->getName() === $this->getStrategyName();
    }

    protected function findParameter(string $parameter, Strategy $strategy): ?string
    {
        $parameters = $strategy->getParameters();

        return $parameters[$parameter] ?? null;
    }
}
