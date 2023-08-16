<?php

namespace Unleash\Client\Variant;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;

interface VariantHandler
{
    public function getDefaultVariant(): Variant;

    public function selectVariant(array $variants, string $groupId, Context $context): ?Variant;
}
