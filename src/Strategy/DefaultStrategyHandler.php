<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;

final class DefaultStrategyHandler extends AbstractStrategyHandler
{
    public function isEnabled(Strategy $strategy, UnleashContext $context): bool
    {
        return true;
    }

    protected function getStrategyName(): string
    {
        return 'default';
    }
}
