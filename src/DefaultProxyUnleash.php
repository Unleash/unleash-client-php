<?php

namespace Unleash\Client;

use Unleash\Client\Configuration\Context;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultResolvedVariant;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\ResolvedVariant;
use Unleash\Client\Metrics\MetricsHandler;
use Unleash\Client\Repository\ProxyRepository;

final class DefaultProxyUnleash implements Unleash
{
    public function __construct(
        private ProxyRepository $repository,
        private MetricsHandler $metricsHandler,
    ) {
    }

    public function register(): bool
    {
        return false;
    }

    public function isEnabled(string $featureName, ?Context $context = null, bool $default = false): bool
    {
        $response = $this->repository->findFeatureByContext($featureName, $context);
        $enabled = $response ? $response->isEnabled() : $default;
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $enabled, []), $enabled);

        return $enabled;
    }

    public function getVariant(string $featureName, ?Context $context = null, ?ResolvedVariant $fallbackVariant = null): ResolvedVariant
    {
        $variant = $fallbackVariant ?? new DefaultResolvedVariant('disabled', false, null);

        $response = $this->repository->findFeatureByContext($featureName, $context);

        if ($response !== null) {
            $variant = $response->getVariants()[0];
        }
        $metricVariant = new DefaultVariant($variant->getName(), $variant->isEnabled(), 0, null, $variant->getPayload());
        $this->metricsHandler->handleMetrics(new DefaultFeature($featureName, $variant->isEnabled(), []), $variant->isEnabled(), $metricVariant);

        return $variant;
    }
}
