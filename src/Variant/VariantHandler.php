<?php

namespace Unleash\Client\Variant;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Variant;

interface VariantHandler
{
    public function getDefaultVariant(): Variant;

    /**
     * @param array<Variant> $variants
     */
    public function selectVariant(array $variants, string $groupId, Context $context): ?Variant;
}
