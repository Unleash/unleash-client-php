<?php

namespace Unleash\Client\DTO\Internal;

use JetBrains\PhpStorm\ExpectedValues;
use Unleash\Client\DTO\Variant;
use Unleash\Client\DTO\VariantPayload;
use Unleash\Client\Enum\Stickiness;

/**
 * @internal
 */
final class UnresolvedVariant implements Variant
{
    public function __construct(
        private readonly string $name,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isEnabled(): bool
    {
        return false;
    }

    public function getPayload(): ?VariantPayload
    {
        return null;
    }

    public function getWeight(): int
    {
        return 0;
    }

    public function getOverrides(): array
    {
        return [];
    }

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    public function getStickiness(): string
    {
        return Stickiness::DEFAULT;
    }

    /**
     * todo Change to null once rector supports it
     *
     * @return null
     *
     * @noinspection PhpMixedReturnTypeCanBeReducedInspection
     */
    public function jsonSerialize(): mixed
    {
        return null;
    }
}
