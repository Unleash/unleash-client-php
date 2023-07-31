<?php

namespace Unleash\Client\Tests\Repository;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\DTO\DefaultResolvedVariant;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\Repository\DefaultUnleashProxyRepository;
use Unleash\Client\Tests\AbstractHttpClientTest;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;

final class DefaultUnleashProxyRepositoryTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait, RealCacheImplementationTrait {
        FakeCacheImplementationTrait::getCache insteadof RealCacheImplementationTrait;
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

        $expectedVariant = new DefaultResolvedVariant('some-variant', true, new DefaultVariantPayload('string', 'some-value'));

        $this->assertEquals('test', $resolvedFeature->getName());
        $this->assertTrue($resolvedFeature->isEnabled());
        $this->assertEquals($expectedVariant, $resolvedFeature->getVariant());
    }
}
