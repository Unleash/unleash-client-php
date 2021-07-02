<?php

namespace Rikudou\Unleash\Repository;

use Rikudou\Unleash\DTO\Feature;

interface UnleashRepository
{
    public function findFeature(string $featureName): ?Feature;

    /**
     * @return iterable<Feature>
     */
    public function getFeatures(): iterable;
}
