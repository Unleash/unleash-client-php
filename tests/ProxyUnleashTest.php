<?php

namespace Unleash\Client\Tests;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response;
use Unleash\Client\ProxyUnleash;
use Unleash\Client\ProxyVariant;
use Unleash\Client\ProxyVariantPayload;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;

final class ProxyUnleashTest extends AbstractHttpClientTest
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
        $payload = new ProxyVariantPayload('string', 'some-value');

        $this->assertEquals($variant, new ProxyVariant('some-variant', true, $payload));
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

        $this->assertEquals($variant, new ProxyVariant('some-variant', true, null));
    }

    public function testMissingVariantReturnsDefault()
    {
        $builder = new TestBuilder([
            "error" => "Failed to find feature with name test"
        ]);
        $unleash = $builder->getInstance();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new ProxyVariant('disabled', false, null));
    }

    public function testVariantWithEmptyPayload()
    {
        $builder = new TestBuilder([
            "name" => "test",
            "enabled" => true,
            "variant" => [
                "name" => "some-variant",
                "payload" => [],
                "enabled" => true
            ],
            "impression_data" => false
        ]);
        $unleash = $builder->getInstance();
        $variant = $unleash->getVariant('test');

        $this->assertEquals($variant, new ProxyVariant('some-variant', true, null));
    }
}

class TestBuilder
{
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

    public function getInstance(): ProxyUnleash
    {
        $handlerStack = HandlerStack::create($this->mockHandler);
        $client = new Client(['handler' => $handlerStack]);

        return new ProxyUnleash(
            'http://localhost',
            'test',
            $client,
            new HttpFactory()
        );
    }
}