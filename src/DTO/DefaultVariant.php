<?php

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Enum\VariantWeightType;

final class DefaultVariant implements Variant
{
    /**
     * @param array<VariantOverride> $overrides
     */
    public function __construct(
        private string $name,
        private bool $enabled,
        private int $weight,
        #[ExpectedValues(valuesFromClass: VariantWeightType::class)]
        private string $weightType,
        #[ExpectedValues(valuesFromClass: Stickiness::class)]
        private string $stickiness,
        private ?VariantPayload $payload,
        private ?array $overrides,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPayload(): ?VariantPayload
    {
        return $this->payload;
    }

    /**
     * @phpstan-return array<string|bool|array>
     */
    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'payload' => 'mixed'])]
    public function jsonSerialize(): array
    {
        return [
            'name' => $this->name,
            'enabled' => $this->enabled,
            'payload' => $this->payload?->jsonSerialize(),
        ];
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    #[ExpectedValues(valuesFromClass: VariantWeightType::class)]
    public function getWeightType(): string
    {
        return $this->weightType;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array
    {
        return $this->overrides ?? [];
    }

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    public function getStickiness(): string
    {
        return $this->stickiness;
    }
}
