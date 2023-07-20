<?php

namespace Unleash\Client\Variant;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\ProxyVariant;

interface VariantHandler
{
    public function getDefaultVariant(): ProxyVariant;

    public function selectVariant(Feature $feature, Context $context): ?ProxyVariant;
}
