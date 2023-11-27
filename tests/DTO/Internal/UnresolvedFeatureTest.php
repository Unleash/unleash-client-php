<?php

namespace Unleash\Client\Tests\DTO\Internal;

use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\Internal\UnresolvedFeature;

final class UnresolvedFeatureTest extends TestCase
{
    /**
     * @var UnresolvedFeature
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new UnresolvedFeature('some_feature_name');
    }

    public function testIsEnabled()
    {
        self::assertFalse($this->instance->isEnabled());
    }

    public function testGetStrategies()
    {
        self::assertCount(0, $this->instance->getStrategies());
    }

    public function testGetVariants()
    {
        self::assertCount(0, $this->instance->getVariants());
    }

    public function testHasImpressionData()
    {
        self::assertFalse($this->instance->hasImpressionData());
    }

    public function testGetDependencies()
    {
        self::assertCount(0, $this->instance->getDependencies());
    }
}
