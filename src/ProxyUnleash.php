<?php

namespace Unleash\Client;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\ProxyVariant;

interface ProxyUnleash
{
    public const SDK_VERSION = '1.10.1';

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool;

    public function getVariant(string $featureName, ?Context $context = null, ?ProxyVariant $fallbackVariant = null): ProxyVariant;
}
