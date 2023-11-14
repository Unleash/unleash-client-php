<?php

namespace Unleash\Client\DTO;

final class DefaultVariantOverride implements VariantOverride
{
    /**
     * @param array<string> $values
     */
    public function __construct(
        private string $field,
        private array $values,
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
