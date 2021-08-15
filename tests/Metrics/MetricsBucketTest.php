<?php

namespace Unleash\Client\Tests\Metrics;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Unleash\Client\Metrics\MetricsBucket;

final class MetricsBucketTest extends TestCase
{
    public function testJsonSerialize()
    {
        $instance = new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable());
        self::assertIsArray($instance->jsonSerialize());

        $instance = new MetricsBucket(new DateTimeImmutable());
        $instance->setEndDate(new DateTimeImmutable());
        self::assertIsArray($instance->jsonSerialize());

        $instance = new MetricsBucket(new DateTimeImmutable());
        $this->expectException(LogicException::class);
        $instance->jsonSerialize();
    }
}
