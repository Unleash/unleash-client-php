<?php

namespace Unleash\Client\Tests\Metrics;

use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Message\RequestInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\Metrics\DefaultMetricsHandler;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Tests\AbstractHttpClientTest;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;

final class DefaultMetricsHandlerTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait, RealCacheImplementationTrait {
        FakeCacheImplementationTrait::getCache insteadof RealCacheImplementationTrait;

        RealCacheImplementationTrait::getCache as getRealCache;
    }

    public function testHandleMetrics()
    {
        $configuration = (new UnleashConfiguration('', '', ''))
            ->setMetricsInterval(0)
            ->setCache($this->getCache());
        $instance = new DefaultMetricsHandler(
            new DefaultMetricsSender(
                $this->httpClient,
                new HttpFactory(),
                $configuration
            ),
            $configuration
        );
        $feature = new DefaultFeature('test', true, []);

        $configuration->setMetricsEnabled(false);
        $instance->handleMetrics($feature, true);
        self::assertCount(0, $this->requestHistory);

        $configuration->setMetricsEnabled(true);
        $this->pushResponse([], 3);
        $instance->handleMetrics($feature, true);
        $instance->handleMetrics($feature, true);
        $instance->handleMetrics($feature, true);
        self::assertCount(3, $this->requestHistory);

        $configuration->setCache($this->getRealCache())
            ->setMetricsInterval(3000);
        $this->requestHistory = [];
        $this->pushResponse([]);

        $instance->handleMetrics($feature, true);
        $instance->handleMetrics($feature, true);
        $instance->handleMetrics($feature, true);
        self::assertCount(0, $this->requestHistory);
        sleep(4);
        $instance->handleMetrics($feature, false);
        self::assertCount(1, $this->requestHistory);

        $request = $this->requestHistory[0]['request'];
        assert($request instanceof RequestInterface);
        $body = json_decode($request->getBody()->getContents(), true);
        self::assertArrayHasKey($feature->getName(), $body['bucket']['toggles']);
        self::assertArrayHasKey('yes', $body['bucket']['toggles'][$feature->getName()]);
        self::assertArrayHasKey('no', $body['bucket']['toggles'][$feature->getName()]);
        self::assertEquals(3, $body['bucket']['toggles'][$feature->getName()]['yes']);
        self::assertEquals(1, $body['bucket']['toggles'][$feature->getName()]['no']);
    }
}
