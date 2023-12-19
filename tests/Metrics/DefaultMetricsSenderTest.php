<?php

namespace Unleash\Client\Tests\Metrics;

use DateTimeImmutable;
use GuzzleHttp\Exception\TransferException;
use GuzzleHttp\Psr7\HttpFactory;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Metrics\MetricsBucket;
use Unleash\Client\Metrics\MetricsBucketToggle;
use Unleash\Client\Tests\AbstractHttpClientTestCase;

final class DefaultMetricsSenderTest extends AbstractHttpClientTestCase
{
    /**
     * @var DefaultMetricsSender
     */
    private $instance;

    /**
     * @var UnleashConfiguration
     */
    private $configuration;

    protected function setUp(): void
    {
        parent::setUp();

        $this->configuration = new UnleashConfiguration('', '', '');
        $this->instance = new DefaultMetricsSender($this->httpClient, new HttpFactory(), $this->configuration);
    }

    public function testSendMetrics()
    {
        $this->configuration->setHeaders([
            'Authorization' => 'test',
        ]);

        $bucket = new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable());
        $bucket
            ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true, null));

        $this->pushResponse([], 1, 202);
        $this->instance->sendMetrics($bucket);
        $this->pushResponse([], 1, 401);
        $this->instance->sendMetrics($bucket);
        $this->configuration->setMetricsEnabled(false);
        $this->instance->sendMetrics($bucket);
        self::assertCount(2, $this->requestHistory);

        $this->configuration->setMetricsEnabled(true);
        $bucket
            ->addToggle(new MetricsBucketToggle(
                new DefaultFeature('tet', true, []),
                true,
                new DefaultVariant('test', true)
            ));
        $this->pushResponse([]);
        $this->instance->sendMetrics($bucket);
    }

    public function testSendMetricsFailure()
    {
        $this->pushResponse(new TransferException());
        $bucket = new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable());
        $this->instance->sendMetrics($bucket);
    }
}
