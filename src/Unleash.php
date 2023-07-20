<?php

namespace Unleash\Client;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\ProxyVariant;

interface Unleash
{
    public const SDK_VERSION = '1.11.2';

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool;

    public function getVariant(string $featureName, ?Context $context = null, ?ProxyVariant $fallbackVariant = null): ProxyVariant;

    public function register(): bool;
}
