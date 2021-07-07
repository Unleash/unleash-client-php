<?php

namespace Rikudou\Tests\Unleash\Metrics;

use Cache\Adapter\Filesystem\FilesystemCachePool;
use GuzzleHttp\Psr7\HttpFactory;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use Psr\Http\Message\RequestInterface;
use Rikudou\Tests\Unleash\AbstractHttpClientTest;
use Rikudou\Tests\Unleash\Traits\FakeCacheImplementationTrait;
use Rikudou\Unleash\Configuration\UnleashConfiguration;
use Rikudou\Unleash\DTO\DefaultFeature;
use Rikudou\Unleash\Metrics\DefaultMetricsHandler;
use Rikudou\Unleash\Metrics\DefaultMetricsSender;

final class DefaultMetricsHandlerTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait;

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

        $cache = new FilesystemCachePool(
            new Filesystem(
                new Local(sys_get_temp_dir() . '/unleash-sdk-tests')
            )
        );
        $cache->clear();
        $configuration->setCache($cache)
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
