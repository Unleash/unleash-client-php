<?php

namespace Unleash\Client;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\ResolvedVariant;

interface Unleash
{
    public const SDK_VERSION = '1.11.2';

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool;

    public function getVariant(string $featureName, ?Context $context = null, ?ResolvedVariant $fallbackVariant = null): ResolvedVariant;

    public function register(): bool;
}
