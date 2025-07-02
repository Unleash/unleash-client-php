<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\ExpectedValues;
use Override;
use Unleash\Client\Enum\Stickiness;

final class DefaultVariant implements Variant
{
    /**
     * @readonly
     */
    private string $name;
    /**
     * @readonly
     */
    private bool $enabled;
    /**
     * @readonly
     */
    private int $weight = 0;
    /**
     * @readonly
     */
    private string $stickiness = Stickiness::DEFAULT;
    /**
     * @readonly
     */
    private ?VariantPayload $payload = null;
    /**
     * @var array<VariantOverride>
     * @readonly
     */
    private ?array $overrides = null;
    /**
     * @readonly
     */
    private bool $featureEnabled = false;
    /**
     * @param array<VariantOverride> $overrides
     */
    public function __construct(
        string $name,
        bool $enabled,
        int $weight = 0,
        #[\JetBrains\PhpStorm\ExpectedValues(valuesFromClass: \Unleash\Client\Enum\Stickiness::class)]
        string $stickiness = Stickiness::DEFAULT,
        ?VariantPayload $payload = null,
        ?array $overrides = null,
        bool $featureEnabled = false
    )
    {
        $this->name = $name;
        $this->enabled = $enabled;
        $this->weight = $weight;
        $this->stickiness = $stickiness;
        $this->payload = $payload;
        $this->overrides = $overrides;
        $this->featureEnabled = $featureEnabled;
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
     * @phpstan-return array<string|bool|array<string>>
     */
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
        // @phpstan-ignore-next-line return.type
        return $result;
    }

    public function getWeight(): int
    {
        return $this->weight;
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
            // @phpstan-ignore-next-line function.alreadyNarrowedType
            $featureEnabled ?? (method_exists($variant, 'isFeatureEnabled') ? $variant->isFeatureEnabled() : false),
        );
    }
}
