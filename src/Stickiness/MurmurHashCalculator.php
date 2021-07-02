<?php

namespace Rikudou\Unleash\Stickiness;

use lastguest\Murmur;

final class MurmurHashCalculator implements StickinessCalculator
{
    public function calculate(string $id, string $groupId): int
    {
        if (!$id) {
            return 0;
        }

        return Murmur::hash3_int("{$id}:{$groupId}") % 100 + 1;
    }
}
