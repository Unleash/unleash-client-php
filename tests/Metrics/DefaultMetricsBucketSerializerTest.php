<?php

namespace Unleash\Client\Tests\Metrics;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\Metrics\DefaultMetricsBucketSerializer;
use Unleash\Client\Metrics\MetricsBucket;
use Unleash\Client\Metrics\MetricsBucketToggle;

final class DefaultMetricsBucketSerializerTest extends TestCase
{
    /**
     * @dataProvider serializeDeserializeData
     */
    public function testSerializeDeserialize(MetricsBucket $bucket)
    {
        $instance = new DefaultMetricsBucketSerializer();
        $deserialized = $instance->deserialize($instance->serialize($bucket));

        self::assertSame($bucket->jsonSerialize(), $deserialized->jsonSerialize());
    }

    private function serializeDeserializeData(): iterable
    {
        yield [new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds'))];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true)),
        ];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), true))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), false))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), false))
                ->addToggle(new MetricsBucketToggle(new DefaultFeature('test', true, []), false)),
        ];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test1', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    false,
                    new DefaultVariant('test1', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test1', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test2', true)
                ))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test3', true)
                )),
        ];
        yield [
            (new MetricsBucket(new DateTimeImmutable(), new DateTimeImmutable('+5 seconds')))
                ->addToggle(new MetricsBucketToggle(
                    new DefaultFeature('test', true, []),
                    true,
                    new DefaultVariant('test1', true)
                )),
        ];
    }
}
