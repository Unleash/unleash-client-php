<?php

namespace Unleash\Client\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultProxyUnleash;
use Unleash\Client\DTO\DefaultProxyVariant;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\Metrics\DefaultMetricsHandler;
use Unleash\Client\Metrics\DefaultMetricsSender;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;

final class DefaultProxyUnleashTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait;

    public function testBasicResolveFeature()
    {
        $builder = new TestBuilder();
        $builder->pushResponse([
            'name' => 'test',
            'enabled' => true,
            'variant' => [
                'name' => 'some-variant',
                'enabled' => true,
            ],
            'impression_data' => false,
        ]);
        $unleash = $builder->build();

        $enabled = $unleash->isEnabled('test');
        $this->assertTrue($enabled);
    }

    public function testResolveNonExistentFeatureReturnsFalse()
    {
        $builder = new TestBuilder();
        $builder->pushResponse([
            'error' => 'Failed to find feature with name test',
        ]);
        $unleash = $builder->build();
        $enabled = $unleash->isEnabled('test');
        $this->assertFalse($enabled);
    }

    public function testResolveFeatureWithNon200Response()
    {
        $builder = new TestBuilder();
        $builder->pushResponse([
            'error' => 'Server Error',
        ], 500);
        $unleash = $builder->build();
        $enabled = $unleash->isEnabled('test');
        $this->assertFalse($enabled);
    }

    public function testResolveFeatureWithInvalidJsonResponse()
    {
        $builder = new TestBuilder();
        $builder->pushResponse('Invalid JSON', 200);
        $unleash = $builder->build();
        $enabled = $unleash->isEnabled('test');
        $this->assertFalse($enabled);
    }

    public function testBasicResolveVariant()
    {
        $builder = new TestBuilder();
        $builder->pushResponse([
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
        $unleash = $builder->build();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('some-variant', true, new DefaultVariantPayload('string', 'some-value')));
    }

    public function testVariantWithoutPayload()
    {
        $builder = new TestBuilder();
        $builder->pushResponse([
            'name' => 'test',
            'enabled' => true,
            'variant' => [
                'name' => 'some-variant',
                'enabled' => true,
            ],
            'impression_data' => false,
        ]);
        $unleash = $builder->build();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('some-variant', true));
    }

    public function testMissingVariantReturnsDefault()
    {
        $builder = new TestBuilder();
        $builder->pushResponse([
            'error' => 'Failed to find feature with name test',
        ]);
        $unleash = $builder->build();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('disabled', false));
    }

    public function testVariantWithNullPayload()
    {
        $builder = new TestBuilder();
        $builder->pushResponse([
            'name' => 'test',
            'enabled' => true,
            'variant' => [
                'name' => 'some-variant',
                'payload' => null,
                'enabled' => true,
            ],
            'impression_data' => false,
        ]);
        $unleash = $builder->build();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('some-variant', true));
    }

    public function testCachingIsRespected()
    {
        $featureName = 'some-cached-feature';
        $featureState = true;
        $psr6Cache = new FilesystemAdapter();
        $cache = new Psr16Cache($psr6Cache);
        $cache->set($featureName, [
            'name' => 'test',
            'enabled' => true,
            'variant' => [
                'name' => 'some-variant',
                'enabled' => true,
            ],
            'impression_data' => false,
        ]);

        $builder = new TestBuilder();
        $builder->withCache($cache);

        $unleash = $builder->build();

        $this->assertEquals($featureState, $unleash->isEnabled($featureName));
    }

    public function testContextIsCorrectlyLayeredIntoUrl()
    {
        $context = new UnleashContext(7, '127.0.0.1', 'some-session', ['hasCustomProperty' => 'true']);
        $expectedUrl = 'http://localhost:4242/features/test?userId=7&sessionId=some-session&remoteAddress=127.0.0.1&properties%5BhasCustomProperty%5D=true';
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], 'OK'),
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $builder = new TestBuilder();
        $builder->withHandlerStack($handler);

        $unleash = $builder->build();

        $enabled = $unleash->isEnabled('test', $context);

        $this->assertFalse($enabled);
        $this->assertCount(1, $container);
        $this->assertEquals($expectedUrl, (string) $container[0]['request']->getUri());
    }

    public function testHeadersArePassedToServer()
    {
        $container = [];
        $history = Middleware::history($container);

        $mock = new MockHandler([
            new Response(200, [], 'OK'),
        ]);

        $handler = HandlerStack::create($mock);
        $handler->push($history);

        $builder = new TestBuilder();
        $builder->withHandlerStack($handler);
        $builder->withHeaders(['customHeader' => 'someCustomHeader']);

        $unleash = $builder->build();

        $enabled = $unleash->isEnabled('test');

        $this->assertFalse($enabled);
        $this->assertCount(1, $container);
        $this->assertEquals('application/json', $container[0]['request']->getHeaderLine('Content-Type'));
        $this->assertEquals('someCustomHeader', $container[0]['request']->getHeaderLine('customHeader'));
    }
}

final class TestBuilder
{
    use FakeCacheImplementationTrait;

    private $mockHandler;

    private $cache;

    private $handler;

    private $handlerStack;

    private $headers;

    private $url;

    public function __construct()
    {
    }

    public function withHandlerStack(HandlerStack $handlerStack): TestBuilder
    {
        $this->handlerStack = $handlerStack;

        return $this;
    }

    public function withCache(CacheInterface $cache): TestBuilder
    {
        $this->cache = $cache;

        return $this;
    }

    public function withHeaders(array $headers): TestBuilder
    {
        $this->headers = $headers;

        return $this;
    }

    public function withUrl(string $url): TestBuilder
    {
        $this->url = $url;

        return $this;
    }

    public function pushResponse($responseBody, int $statusCode = 200)
    {
        $this->mockHandler = new MockHandler();
        $mockResponse = new Response(
            $statusCode,
            ['ETag' => 'etag value'],
            is_array($responseBody) ? json_encode($responseBody) : $responseBody
        );
        $this->mockHandler->append($mockResponse);
    }

    public function build(): DefaultProxyUnleash
    {
        $url = $this->url ?? 'http://localhost:4242';
        $handlerStack = $this->handlerStack ?? HandlerStack::create($this->mockHandler);
        $this->cache = $this->cache ?? $this->getCache();
        $client = new Client(['handler' => $handlerStack]);
        $config = new UnleashConfiguration($url, 'some-app', 'some-instance', $this->cache);
        if ($this->headers) {
            $config->setHeaders($this->headers);
        }

        $requestFactory = new HttpFactory();
        $metricsHandler = new DefaultMetricsHandler(
            new DefaultMetricsSender(
                $client,
                $requestFactory,
                $config
            ),
            $config
        );

        return new DefaultProxyUnleash(
            $url,
            $config,
            $client,
            $requestFactory,
            $metricsHandler,
            $this->cache
        );
    }
}
