<?php

namespace Unleash\Client\Tests\Repository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Helper\Url;
use Unleash\Client\Repository\DefaultUnleashProxyRepository;
use Unleash\Client\Tests\AbstractHttpClientTestCase;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;

final class DefaultUnleashProxyRepositoryTest extends AbstractHttpClientTestCase
{
    use FakeCacheImplementationTrait, RealCacheImplementationTrait {
        FakeCacheImplementationTrait::getCache insteadof RealCacheImplementationTrait;

        RealCacheImplementationTrait::getCache as getRealCache;
    }

    public function testNon200ResponseDegradesGracefully()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(400, [], 'Error, bad request'),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);
        $config = (new UnleashConfiguration('', '', ''))
            ->setCache($this->getCache())
            ->setProxyKey('some-key')
            ->setTtl(5);

        $requestFactory = new HttpFactory();

        $repository = new DefaultUnleashProxyRepository($config, $client, $requestFactory);
        $resolvedFeature = $repository->findFeatureByContext('some-feature');
        $this->assertNull($resolvedFeature);
    }

    public function test200ResponseResolvesCorrectly()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(
                200,
                ['ETag' => 'etag value'],
                json_encode([
                    'name' => 'test',
                    'enabled' => true,
                    'variant' => [
                        'name' => 'some-variant',
                        'payload' => [
                            'type' => 'string',
                            'value' => 'some-value',
                        ],
                        'enabled' => true,
                    ],
                    'impression_data' => false,
                ])
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);
        $config = (new UnleashConfiguration('', '', ''))
            ->setCache($this->getCache())
            ->setProxyKey('some-key')
            ->setTtl(5);

        $requestFactory = new HttpFactory();

        $repository = new DefaultUnleashProxyRepository($config, $client, $requestFactory);
        $resolvedFeature = $repository->findFeatureByContext('test');

        $expectedVariant = new DefaultVariant('some-variant', true, 0, Stickiness::DEFAULT, new DefaultVariantPayload('string', 'some-value'));

        $this->assertEquals('test', $resolvedFeature->getName());
        $this->assertTrue($resolvedFeature->isEnabled());
        $this->assertEquals($expectedVariant, $resolvedFeature->getVariant());
    }

    public function testCacheTtlIsRespected()
    {
        $container = [];
        $history = Middleware::history($container);
        $response = json_encode([
            'name' => 'test',
            'enabled' => true,
            'variant' => [
                'name' => 'some-variant',
                'payload' => [
                    'type' => 'string',
                    'value' => 'some-value',
                ],
                'enabled' => true,
            ],
            'impression_data' => false,
        ]);

        $mock = new MockHandler([
            new Response(
                200,
                ['ETag' => 'etag value'],
                $response
            ),
            new Response(
                200,
                ['ETag' => 'etag value'],
                $response
            ),
        ]);

        $handlerStack = HandlerStack::create($mock);
        $handlerStack->push($history);

        $client = new Client(['handler' => $handlerStack]);
        $config = (new UnleashConfiguration('', '', ''))
            ->setCache($this->getRealCache())
            ->setProxyKey('some-key')
            ->setTtl(1);

        $requestFactory = new HttpFactory();
        $repository = new DefaultUnleashProxyRepository($config, $client, $requestFactory);

        $repository->findFeatureByContext('test');
        //cache is still warm so this should fall back to that
        $repository->findFeatureByContext('test');

        sleep(1);

        //ttl should have expired so this should trigger an API call
        $repository->findFeatureByContext('test');

        $this->assertCount(2, $container);
    }

    public function testUrl()
    {
        $configuration = (new UnleashConfiguration(
            new Url('https://localhost/api', 'somePrefix'),
            '',
            ''
        ))->setCache($this->getCache())->setProxyKey('test');
        $instance = new DefaultUnleashProxyRepository($configuration, $this->httpClient, new HttpFactory());
        $this->pushResponse([]);

        $instance->findFeatureByContext('testFeature');
        self::assertCount(1, $this->requestHistory);
        self::assertSame('https://localhost/api/frontend/features/testFeature?namePrefix=somePrefix', (string) $this->requestHistory[0]['request']->getUri());
    }
}
