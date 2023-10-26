<?php

namespace Unleash\Client\DTO;

use JsonSerializable;

interface Dependency extends JsonSerializable
{
    public function getFeature(): Feature | string;

    public function getEnabled(): ?bool;

    /**
     * @return array<string>
     */
    public function getVariants(): ?array;
}
