<?php

namespace Unleash\Client;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Variant;

interface Unleash
{
    public const string SDK_NAME = 'unleash-client-php';

    public const string SDK_VERSION = '2.9.0';

    public const string SPECIFICATION_VERSION = '5.1.6';

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool;

    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant;

    public function register(): bool;
}
