<?php

namespace Unleash\Client\Repository;

use Unleash\Client\DTO\Feature;

interface UnleashRepository
{
    public function findFeature(string $featureName): ?Feature;

    /**
     * @return iterable<Feature>
     */
    public function getFeatures(): iterable;
}
