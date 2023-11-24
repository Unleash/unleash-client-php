<?php

namespace Unleash\Client\Tests\DTO\Internal;

use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\Internal\UnresolvedVariant;
use Unleash\Client\Enum\Stickiness;

final class UnresolvedVariantTest extends TestCase
{
    /**
     * @var UnresolvedVariant
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new UnresolvedVariant('test');
    }

    public function testIsEnabled()
    {
        self::assertFalse($this->instance->isEnabled());
    }

    public function testGetPayload()
    {
        self::assertNull($this->instance->getPayload());
    }

    public function testGetWeight()
    {
        self::assertSame(0, $this->instance->getWeight());
    }

    public function testGetOverrides()
    {
        self::assertCount(0, $this->instance->getOverrides());
    }

    public function testGetStickiness()
    {
        self::assertSame(Stickiness::DEFAULT, $this->instance->getStickiness());
    }

    public function testJsonSerialize()
    {
        self::assertNull($this->instance->jsonSerialize());
    }
}
