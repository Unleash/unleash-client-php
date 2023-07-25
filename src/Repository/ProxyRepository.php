<?php

namespace Unleash\Client\Repository;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\Feature;

interface ProxyRepository
{
    public function findFeatureByContext(string $featureName, ?Context $context = null): ?Feature;
}
