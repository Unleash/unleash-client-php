<?php

namespace Unleash\Client\Strategy;

use Override;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;

final class DefaultStrategyHandler extends AbstractStrategyHandler
{
    #[Override]
    public function isEnabled(Strategy $strategy, Context $context): bool
    {
        if (!$this->validateConstraints($strategy, $context)) {
            return false;
        }

        return true;
    }

    #[Override]
    public function getStrategyName(): string
    {
        return 'default';
    }
}
