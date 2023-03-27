<?php

namespace Unleash\Client\Helper\Builder;

use Unleash\Client\Stickiness\StickinessCalculator;

interface StickinessCalculatorAware
{
    public function setStickinessCalculator(StickinessCalculator $stickinessCalculator): void;
}
