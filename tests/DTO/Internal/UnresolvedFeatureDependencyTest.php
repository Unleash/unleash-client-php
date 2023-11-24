<?php

namespace Unleash\Client\Tests\DTO\Internal;

use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\Internal\UnresolvedFeatureDependency;

final class UnresolvedFeatureDependencyTest extends TestCase
{
    /**
     * @var UnresolvedFeatureDependency
     */
    private $instance;

    protected function setUp(): void
    {
        $this->instance = new UnresolvedFeatureDependency(
            new DefaultFeature('test', false, []),
            true,
            [],
        );
    }

    public function testIsResolved()
    {
        self::assertFalse($this->instance->isResolved());
    }
}
