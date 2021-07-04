<?php

namespace Rikudou\Unleash\Variant;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\DTO\Variant;

interface VariantHandler
{
    public function getDefaultVariant(): Variant;

    public function selectVariant(Feature $feature, UnleashContext $context): ?Variant;
}
