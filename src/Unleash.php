<?php

namespace Rikudou\Unleash;

use Rikudou\Unleash\Configuration\UnleashContext;

interface Unleash
{
    public function isEnabled(string $featureName, UnleashContext $context = null, bool $default = false): bool;
}
