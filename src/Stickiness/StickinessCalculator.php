<?php

namespace Unleash\Client\Stickiness;

interface StickinessCalculator
{
    public function calculate(
        string $id,
        string $groupId,
        int $normalizer = 100,
        int $seed = 0
    ): int;
}
