<?php

namespace Unleash\Client\Tests\DTO;

use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\DefaultProxyFeature;
use Unleash\Client\DTO\DefaultVariant;

final class DefaultProxyFeatureTest extends TestCase
{
    public function testGetProperties()
    {
        $instance = new DefaultProxyFeature(['name' => 'test', 'enabled' => true, 'impressionData' => true, 'variant' => ['name' => 'someVariant', 'enabled' => true, 'payload' => ['type' => 'string', 'value' => 'test']]]);
        self::assertIsString($instance->getName());
        self::assertIsBool($instance->isEnabled());
        self::assertIsBool($instance->hasImpressionData());
        self::assertInstanceOf(DefaultVariant::class, $instance->getVariant());

        self::assertEquals('test', $instance->getName());
        self::assertTrue($instance->isEnabled());
        self::assertTrue($instance->hasImpressionData());
        self::assertEquals('someVariant', $instance->getVariant()->getName());
    }

    public function testGetPropertiesImpressionDataSnakeCaseBackwardsCompatibility()
    {
        $instance = new DefaultProxyFeature(['name' => 'test', 'enabled' => true, 'impression_data' => true, 'variant' => ['name' => 'someVariant', 'enabled' => true, 'payload' => ['type' => 'string', 'value' => 'test']]]);
        self::assertIsString($instance->getName());
        self::assertIsBool($instance->isEnabled());
        self::assertIsBool($instance->hasImpressionData());
        self::assertInstanceOf(DefaultVariant::class, $instance->getVariant());

        self::assertEquals('test', $instance->getName());
        self::assertTrue($instance->isEnabled());
        self::assertTrue($instance->hasImpressionData());
        self::assertEquals('someVariant', $instance->getVariant()->getName());
    }

    public function testToJson()
    {
        $instance = new DefaultProxyFeature(['name' => 'test', 'enabled' => true, 'impressionData' => true, 'variant' => ['name' => 'someVariant', 'enabled' => true, 'payload' => ['type' => 'string', 'value' => 'test']]]);
        $json = json_encode($instance);
        self::assertEquals('{"name":"test","enabled":true,"variant":{"name":"someVariant","enabled":true,"feature_enabled":false,"payload":{"type":"string","value":"test"}},"impression_data":true,"impressionData":true}', $json);
    }

    public function testToJsonFromSnakeCaseImpressionDataForBackwardsCompatibility()
    {
        $instance = new DefaultProxyFeature(['name' => 'test', 'enabled' => true, 'impression_data' => true, 'variant' => ['name' => 'someVariant', 'enabled' => true, 'payload' => ['type' => 'string', 'value' => 'test']]]);
        $json = json_encode($instance);
        self::assertEquals('{"name":"test","enabled":true,"variant":{"name":"someVariant","enabled":true,"feature_enabled":false,"payload":{"type":"string","value":"test"}},"impression_data":true,"impressionData":true}', $json);
    }
}
