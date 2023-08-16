<?php

namespace Unleash\Client\DTO;


interface FeatureEnabledResult
{

    public function isEnabled(): bool;

    public function getStrategy(): ?Strategy;
}
