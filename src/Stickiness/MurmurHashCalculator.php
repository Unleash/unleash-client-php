<?php

namespace Rikudou\Unleash\Stickiness;

use lastguest\Murmur;

final class MurmurHashCalculator implements StickinessCalculator
{
    /**
     * @codeCoverageIgnore
     */
    public function calculate(string $id, string $groupId): int
    {
        return Murmur::hash3_int("{$groupId}:{$id}") % 100 + 1;
    }
}
