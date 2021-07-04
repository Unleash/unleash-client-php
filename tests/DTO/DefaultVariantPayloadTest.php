<?php

namespace Rikudou\Tests\Unleash\DTO;

use LogicException;
use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\DTO\DefaultVariantPayload;
use Rikudou\Unleash\Enum\VariantPayloadType;

final class DefaultVariantPayloadTest extends TestCase
{
    public function testGetValue()
    {
        $instance = new DefaultVariantPayload(VariantPayloadType::STRING, 'test');
        self::assertIsString($instance->getValue());

        $instance = new DefaultVariantPayload(VariantPayloadType::JSON, '{"test": "test"}');
        self::assertIsString($instance->getValue());

        $instance = new DefaultVariantPayload(VariantPayloadType::CSV, "Name,Value\nTest,Test");
        self::assertIsString($instance->getValue());
    }

    public function testFromJson()
    {
        $instance = new DefaultVariantPayload(VariantPayloadType::JSON, '{"test": "test"}');
        self::assertIsArray($instance->fromJson());

        try {
            $instance = new DefaultVariantPayload(VariantPayloadType::STRING, 'test');
            $instance->fromJson();
            $this->fail('Expected an exception for unsupported string type');
        } catch (LogicException $e) {
        }

        $instance = new DefaultVariantPayload(VariantPayloadType::CSV, "Name,Value\nTest,Test");
        $this->expectException(LogicException::class);
        $instance->fromJson();
    }
}
