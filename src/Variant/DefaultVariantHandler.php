<?php

namespace Unleash\Client\Variant;

use JetBrains\PhpStorm\Pure;
use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Stickiness\StickinessCalculator;

final class DefaultVariantHandler implements VariantHandler
{
    /**
     * @readonly
     */
    private StickinessCalculator $stickinessCalculator;
    public function __construct(StickinessCalculator $stickinessCalculator)
    {
        $this->stickinessCalculator = $stickinessCalculator;
    }
    #[Pure]
    public function getDefaultVariant(): Variant
    {
        return new DefaultVariant('disabled', false, 0, Stickiness::DEFAULT, null, null);
    }

    public function selectVariant(Feature $feature, Context $context): ?Variant
    {
        $totalWeight = 0;
        foreach ($feature->getVariants() as $variant) {
            $totalWeight += $variant->getWeight();
        }
        if ($totalWeight <= 0) {
            return null;
        }

        if ($overridden = $this->findOverriddenVariant($feature, $context)) {
            return $overridden;
        }

        $stickiness = $this->calculateStickiness($feature, $context, $totalWeight);

        $counter = 0;
        foreach ($feature->getVariants() as $variant) {
            if ($variant->getWeight() <= 0) {
                continue;
            }
            $counter += $variant->getWeight();
            if ($counter >= $stickiness) {
                return $variant;
            }
        }

        // @codeCoverageIgnoreStart
        return null;
        // @codeCoverageIgnoreEnd
    }

    private function findOverriddenVariant(Feature $feature, Context $context): ?Variant
    {
        foreach ($feature->getVariants() as $variant) {
            foreach ($variant->getOverrides() as $override) {
                if ($context->hasMatchingFieldValue($override->getField(), $override->getValues())) {
                    return $variant;
                }
            }
        }

        return null;
    }

    private function calculateStickiness(
        Feature $feature,
        Context $context,
        int $totalWeight
    ): int {
        $stickiness = $feature->getVariants()[0]->getStickiness();
        if ($stickiness !== Stickiness::DEFAULT) {
            $seed = $context->findContextValue($stickiness) ?? $this->randomString();
        } else {
            $seed = $context->getCurrentUserId()
                ?? $context->getSessionId()
                ?? $context->getIpAddress()
                ?? $this->randomString();
        }

        return $this->stickinessCalculator->calculate($seed, $feature->getName(), $totalWeight);
    }

    private function randomString(): string
    {
        return (string) random_int(1, 100_000);
    }
}
