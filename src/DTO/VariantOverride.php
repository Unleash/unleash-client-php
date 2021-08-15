<?php

namespace Unleash\Client\DTO;

interface VariantOverride
{
    public function getField(): string;

    /**
     * @return array<string>
     */
    public function getValues(): array;
}
