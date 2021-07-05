<?php

namespace Rikudou\Unleash;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Variant;

interface Unleash
{
    public const SDK_VERSION = '0.12.0';

    public function isEnabled(string $featureName, UnleashContext $context = null, bool $default = false): bool;

    public function getVariant(string $featureName, ?UnleashContext $context = null, ?Variant $fallbackVariant = null): Variant;

    public function register(): bool;
}
