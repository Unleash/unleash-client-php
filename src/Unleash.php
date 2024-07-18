<?php

namespace Unleash\Client;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Variant;

interface Unleash
{
    /**
     * @var string
     */
    public const SDK_VERSION = '2.5.0';

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool;

    public function getVariant(string $featureName, ?Context $context = null, ?Variant $fallbackVariant = null): Variant;

    public function register(): bool;
}
