<?php

namespace Unleash\Client\Tests\Metrics;

use DateTimeImmutable;
use GuzzleHttp\Psr7\HttpFactory;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Metrics\MetricsBucket;
use Unleash\Client\Metrics\MetricsBucketToggle;
use Unleash\Client\Tests\AbstractHttpClientTest;

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
