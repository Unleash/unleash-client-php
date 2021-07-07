<?php

namespace Rikudou\Tests\Unleash\Metrics;

use DateTimeImmutable;
use GuzzleHttp\Psr7\HttpFactory;
use Rikudou\Tests\Unleash\AbstractHttpClientTest;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\DTO\DefaultFeature;
use Rikudou\Unleash\DTO\DefaultVariant;
use Rikudou\Unleash\Metrics\DefaultMetricsSender;
use Rikudou\Unleash\Metrics\MetricsBucket;
use Rikudou\Unleash\Metrics\MetricsBucketToggle;

final class DefaultMetricsSenderTest extends AbstractHttpClientTest
{
    public function testSendMetrics()
    {
        $configuration = new UnleashConfiguration('', '', '');
        $configuration->setHeaders([
            'Authorization' => 'test',
        ]);

        $instance = new DefaultMetricsSender(
            $this->httpClient,
            new HttpFactory(),
            $configuration
        );
        $bucket = new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable());
        $bucket
            ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true, null));

        $this->pushResponse([], 1, 202);
        $instance->sendMetrics($bucket);
        $this->pushResponse([], 1, 401);
        $instance->sendMetrics($bucket);
        $configuration->setMetricsEnabled(false);
        $instance->sendMetrics($bucket);
        self::assertCount(2, $this->requestHistory);

        $configuration->setMetricsEnabled(true);
        $bucket
            ->addToggle(new MetricsBucketToggle(
                new DefaultFeature('tet', true, []),
                true,
                new DefaultVariant('test', true)
            ));
        $this->pushResponse([]);
        $instance->sendMetrics($bucket);
    }
}
