<?php

namespace Rikudou\Unleash\Variant;

use Rikudou\Unleash\Configuration\Context;
use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\DTO\Variant;

interface VariantHandler
{
    public function getDefaultVariant(): Variant;

    public function selectVariant(Feature $feature, Context $context): ?Variant;
}
