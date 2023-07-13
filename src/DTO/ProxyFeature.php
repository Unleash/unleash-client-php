<?php

namespace Unleash\Client\DTO;

interface ProxyFeature
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getVariant(): ProxyVariant;

    public function hasImpressionData(): bool;
}
