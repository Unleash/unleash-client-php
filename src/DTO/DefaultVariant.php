<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Override;
use Unleash\Client\Enum\Stickiness;

final readonly class DefaultVariant implements Variant
{
    /**
     * @param array<VariantOverride> $overrides
     */
    public function __construct(
        private string $name,
        private bool $enabled,
        private int $weight = 0,
        #[ExpectedValues(valuesFromClass: Stickiness::class)]
        private string $stickiness = Stickiness::DEFAULT,
        private ?VariantPayload $payload = null,
        private ?array $overrides = null,
        private bool $featureEnabled = false,
    ) {
    }

    #[Override]
    public function getName(): string
    {
        return $this->name;
    }

    #[Override]
    public function getPayload(): ?VariantPayload
    {
        return $this->payload;
    }

    /**
     * @phpstan-return array<string|bool|array<string>>
     */
    #[ArrayShape(['name' => 'string', 'enabled' => 'bool', 'payload' => 'mixed'])]
    #[Override]
    public function jsonSerialize(): array
    {
        $result = [
            'name' => $this->name,
            'enabled' => $this->enabled,
            'feature_enabled' => $this->featureEnabled,
        ];
        if ($this->payload !== null) {
            $result['payload'] = $this->payload->jsonSerialize();
            assert(is_array($result['payload']));
        }

        return $result;
    }

    #[Override]
    public function getWeight(): int
    {
        return $this->weight;
    }

    #[Override]
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @return array<VariantOverride>
     */
    #[Override]
    public function getOverrides(): array
    {
        return $this->overrides ?? [];
    }

    #[ExpectedValues(valuesFromClass: Stickiness::class)]
    #[Override]
    public function getStickiness(): string
    {
        return $this->stickiness;
    }

    public function isFeatureEnabled(): bool
    {
        return $this->featureEnabled;
    }

    public static function fromVariant(Variant $variant, ?bool $featureEnabled = null): self
    {
        return new self(
            $variant->getName(),
            $variant->isEnabled(),
            $variant->getWeight(),
            $variant->getStickiness(),
            $variant->getPayload(),
            $variant->getOverrides(),
            $featureEnabled ?? (method_exists($variant, 'isFeatureEnabled') ? $variant->isFeatureEnabled() : false),
        );
    }
}
