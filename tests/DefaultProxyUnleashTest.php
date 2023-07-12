<?php

namespace Unleash\Client\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Unleash\Client\Configuration\UnleashConfiguration;
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
        $builder = new TestBuilder([
            "name" => "test",
            "enabled" => true,
            "variant" => [
                "name" => "some-variant",
                "enabled" => true
            ],
            "impression_data" => false
        ]);
        $unleash = $builder->getInstance();
        $enabled = $unleash->isEnabled('test');
        $this->assertTrue($enabled);
    }

    public function testResolveNonExistentFeatureReturnsFalse()
    {
        $builder = new TestBuilder([
            "error" => "Failed to find feature with name test"
        ]);
        $unleash = $builder->getInstance();
        $enabled = $unleash->isEnabled('test');
        $this->assertFalse($enabled);
    }

    public function testResolveFeatureWithNon200Response()
    {
        $builder = new TestBuilder([
            "error" => "Server Error"
        ], 500);
        $unleash = $builder->getInstance();
        $enabled = $unleash->isEnabled('test');
        $this->assertFalse($enabled);
    }

    public function testResolveFeatureWithInvalidJsonResponse()
    {
        $builder = new TestBuilder("Invalid JSON", 200);
        $unleash = $builder->getInstance();
        $enabled = $unleash->isEnabled('test');
        $this->assertFalse($enabled);
    }

    public function testBasicResolveVariant()
    {
        $builder = new TestBuilder([
            "name" => "test",
            "enabled" => true,
            "variant" => [
                "name" => "some-variant",
                "payload" => [
                    "type" => "string",
                    "value" => "some-value"
                ],
                "enabled" => true
            ],
            "impression_data" => false
        ]);
        $unleash = $builder->getInstance();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('some-variant', true, new DefaultVariantPayload("string", "some-value")));
    }

    public function testVariantWithoutPayload()
    {
        $builder = new TestBuilder([
            "name" => "test",
            "enabled" => true,
            "variant" => [
                "name" => "some-variant",
                "enabled" => true
            ],
            "impression_data" => false
        ]);
        $unleash = $builder->getInstance();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('some-variant', true));
    }

    public function testMissingVariantReturnsDefault()
    {
        $builder = new TestBuilder([
            "error" => "Failed to find feature with name test"
        ]);
        $unleash = $builder->getInstance();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('disabled', false));
    }

    public function testVariantWithNullPayload()
    {
        $builder = new TestBuilder([
            "name" => "test",
            "enabled" => true,
            "variant" => [
                "name" => "some-variant",
                "payload" => null,
                "enabled" => true
            ],
            "impression_data" => false
        ]);
        $unleash = $builder->getInstance();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new DefaultProxyVariant('some-variant', true));
    }
}

class TestBuilder
{
    use FakeCacheImplementationTrait;
    private $mockHandler;

    public function __construct(mixed $responseBody, int $statusCode = 200)
    {
        $this->mockHandler = new MockHandler();
        $mockResponse = new Response(
            $statusCode,
            ['ETag' => 'etag value'],
            is_array($responseBody) ? json_encode($responseBody) : $responseBody
        );
        $this->mockHandler->append($mockResponse);
    }

    public function getInstance(): DefaultProxyUnleash
    {
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);
        $config = new UnleashConfiguration('localhost:4242', 'some-app', 'some-instance', $this->getCache());
        $requestFactory = new HttpFactory();
        $metricsHandler = new DefaultMetricsHandler(
            new DefaultMetricsSender(
                $client,
                $requestFactory,
                $config,
            ),
            $config
        );

        return new DefaultProxyUnleash(
            'http://localhost',
            $config,
            $client,
            $requestFactory,
            $metricsHandler,
            $this->getCache()
        );
    }
}