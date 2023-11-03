<?php

namespace Unleash\Client\DTO;

final class DefaultFeatureEnabledResult implements FeatureEnabledResult
{
    public function __construct(
        private bool $isEnabled = false,
        private ?Strategy $strategy = null,
    ) {
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function getStrategy(): ?Strategy
    {
        return $this->strategy;
    }
}
