<?php

namespace Unleash\Client\DTO;

use JsonSerializable;

interface Dependency extends JsonSerializable
{
    public function getName(): string;

    public function getEnabled(): ?bool;

    /**
     * @return array<string>
     */
    public function getVariants(): ?array;
}
