<?php

namespace Rikudou\Unleash\Metrics;

use DateTimeImmutable;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\DTO\Feature;
use Rikudou\Unleash\DTO\Variant;

final class DefaultMetricsHandler implements MetricsHandler
{
    private const CACHE_KEY_BUCKET = 'rikudou.unleash.bucket';

    public function __construct(
        private MetricsSender $metricsSender,
        private UnleashConfiguration $configuration
    ) {
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
        if ($cache !== null && $cache->has(self::CACHE_KEY_BUCKET)) {
            return $cache->get(self::CACHE_KEY_BUCKET);
        }

        return new MetricsBucket(new DateTimeImmutable());
    }

    private function shouldSend(MetricsBucket $bucket): bool
    {
        if ($this->configuration->getCache() !== null) {
            $bucketStartDate = $bucket->getStartDate();
            $nowMilliseconds = (int) (microtime(true) * 1000);
            $startDateMilliseconds = (int) (
                ($bucketStartDate->getTimestamp() + (int) $bucketStartDate->format('v') / 1000) * 1_000
            );
            $diff = $nowMilliseconds - $startDateMilliseconds;

            return $diff > $this->configuration->getMetricsInterval();
        }

        return true;
    }

    private function send(MetricsBucket $bucket): void
    {
        $bucket->setEndDate(new DateTimeImmutable());
        $this->metricsSender->sendMetrics($bucket);
        if ($cache = $this->configuration->getCache()) {
            $cache->delete(self::CACHE_KEY_BUCKET);
        }
    }

    private function store(MetricsBucket $bucket): void
    {
        $cache = $this->configuration->getCache();
        assert($cache !== null);
        $cache->set(self::CACHE_KEY_BUCKET, $bucket);
    }
}
