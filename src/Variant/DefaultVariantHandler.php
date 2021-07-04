<?php

namespace Rikudou\Unleash\Variant;

use JetBrains\PhpStorm\Pure;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultVariant;
use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\DTO\Variant;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Enum\VariantWeightType;
use Rikudou\Unleash\Stickiness\StickinessCalculator;

final class DefaultVariantHandler implements VariantHandler
{
    public function __construct(
        private StickinessCalculator $stickinessCalculator,
    ) {
    }

    #[Pure]
    public function getDefaultVariant(): Variant
    {
        return new DefaultVariant(
            'disabled',
            false,
            0,
            VariantWeightType::FIXED,
            Stickiness::DEFAULT,
            null,
            null,
        );
    }

    public function selectVariant(Feature $feature, UnleashContext $context): ?Variant
    {
        $totalWeight = (function () use ($feature): int {
            $result = 0;
            foreach ($feature->getVariants() as $variant) {
                $result += $variant->getWeight();
            }

            return $result;
        })();
        if ($totalWeight <= 0) {
            return null;
        }

        if ($overridden = $this->findOverriddenVariant($feature, $context)) {
            return $overridden;
        }

        $stickiness = $this->calculateStickiness(
            $feature,
            $context,
            $totalWeight,
        );

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

        return null;
    }

    private function findOverriddenVariant(Feature $feature, UnleashContext $context): ?Variant
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
        UnleashContext $context,
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
