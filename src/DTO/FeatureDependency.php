<?php

namespace Unleash\Client\DTO;

interface FeatureDependency
{
    public function getFeature(): ?Feature;

    public function getExpectedState(): bool;

    /**
     * @return array<Variant>|null
     */
    public function getRequiredVariants(): ?array;

    public function isResolved(): bool;
}
