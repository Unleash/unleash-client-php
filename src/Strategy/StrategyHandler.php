<?php

namespace Rikudou\Unleash\Strategy;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Strategy;

interface StrategyHandler
{
    public function supports(Strategy $strategy): bool;

    public function getStrategyName(): string;

    public function isEnabled(Strategy $strategy, UnleashContext $context): bool;
}
