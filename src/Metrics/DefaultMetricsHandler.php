<?php

namespace Unleash\Client\Metrics;

use DateTimeImmutable;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Variant;
use Unleash\Client\Enum\CacheKey;

final class DefaultMetricsHandler implements MetricsHandler
{
    private MetricsSender $metricsSender;
    private UnleashConfiguration $configuration;
    public function __construct(MetricsSender $metricsSender, UnleashConfiguration $configuration)
    {
        $this->metricsSender = $metricsSender;
        $this->configuration = $configuration;
    }

    public function handleMetrics(Feature $feature, bool $successful, Variant $variant = null): void
    {
        if (!$this->configuration->isMetricsEnabled()) {
            return;
        }

        $bucket = $this->getOrCreateBucket($feature);
        $bucket->addToggle(new MetricsBucketToggle($feature, $successful, $variant));
        if ($this->shouldSend($bucket)) {
            $this->send($bucket);
        } else {
            $this->store($bucket);
        }
    }

    private function getOrCreateBucket(Feature $feature): MetricsBucket
    {
        $cache = $this->configuration->getCache();
        if ($cache->has(CacheKey::METRICS_BUCKET)) {
            return $cache->get(CacheKey::METRICS_BUCKET);
        }

        return new MetricsBucket(new DateTimeImmutable());
    }

    private function shouldSend(MetricsBucket $bucket): bool
    {
        $bucketStartDate = $bucket->getStartDate();
        $nowMilliseconds = (int) (microtime(true) * 1000);
        $startDateMilliseconds = (int) (
            ($bucketStartDate->getTimestamp() + (int) $bucketStartDate->format('v') / 1000) * 1_000
        );
        $diff = $nowMilliseconds - $startDateMilliseconds;

        return $diff >= $this->configuration->getMetricsInterval();
    }

    private function send(MetricsBucket $bucket): void
    {
        $bucket->setEndDate(new DateTimeImmutable());
        $this->metricsSender->sendMetrics($bucket);
        $cache = $this->configuration->getCache();
        if ($cache->has(CacheKey::METRICS_BUCKET)) {
            $cache->delete(CacheKey::METRICS_BUCKET);
        }
    }

    private function store(MetricsBucket $bucket): void
    {
        $cache = $this->configuration->getCache();
        $cache->set(CacheKey::METRICS_BUCKET, $bucket);
    }
}
