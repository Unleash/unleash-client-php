<?php

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use Rikudou\Unleash\Enum\Stickiness;

interface Variant extends JsonSerializable
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getPayload(): ?VariantPayload;

    public function getWeight(): int;

    public function getWeightType(): string;

    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array;

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    public function getStickiness(): string;
}
