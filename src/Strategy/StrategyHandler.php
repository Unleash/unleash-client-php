<?php

namespace Unleash\Client\Strategy;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Strategy;

interface StrategyHandler
{
    public function supports(Strategy $strategy): bool;

    public function getStrategyName(): string;

    public function isEnabled(Strategy $strategy, Context $context): bool;
}
