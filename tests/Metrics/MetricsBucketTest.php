<?php

namespace Rikudou\Tests\Unleash\Metrics;

use DateTimeImmutable;
use LogicException;
use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Metrics\MetricsBucket;

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
