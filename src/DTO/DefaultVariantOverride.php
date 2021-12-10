<?php

namespace Unleash\Client\DTO;

final class DefaultVariantOverride implements VariantOverride
{
    /**
     * @param array<string> $values
     */
    public function __construct(
        private readonly string $field,
        private readonly array $values,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @return array<string>
     */
    public function getValues(): array
    {
        return $this->values;
    }
}
