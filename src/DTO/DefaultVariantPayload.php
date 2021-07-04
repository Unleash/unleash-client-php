<?php

/** @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection */

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Rikudou\Unleash\Enum\VariantPayloadType;

final class DefaultVariantPayload implements VariantPayload
{
    public function __construct(
        #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
        private string $type,
        private string $value,
    ) {
    }

    /**
     * @codeCoverageIgnore
     */
    #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array<mixed>|string
     */
    public function getValue(): array|string
    {
        return match ($this->type) {
            VariantPayloadType::JSON => json_decode($this->value, true, flags: JSON_THROW_ON_ERROR),
            default => $this->value,
        };
    }

    /**
     * @phpstan-return array<string|array>
     */
    #[ArrayShape(['type' => 'string', 'value' => 'array|mixed[]|string'])]
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->getValue(),
        ];
    }
}
