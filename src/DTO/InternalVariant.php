<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use Unleash\Client\Enum\Stickiness;

interface InternalVariant extends JsonSerializable
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getPayload(): ?VariantPayload;

    public function getWeight(): int;

    /**
     * @return array<VariantOverride>
     */
    public function getOverrides(): array;

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    public function getStickiness(): string;
}
