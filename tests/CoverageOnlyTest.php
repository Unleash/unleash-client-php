<?php

namespace Unleash\Client\Tests;

use PHPUnit\Framework\TestCase;
use Unleash\Client\DTO\DefaultSegment;

/**
 * This class is only for triggering code that doesn't really make sense to test and is here to achieve 100% code coverage.
 * The reason is to catch potential problems during transpilation to lower versions of php.
 */
final class CoverageOnlyTest extends TestCase
{
    public function testDefaultSegment(): void
    {
        $instance = new DefaultSegment(1, []);
        self::assertEquals(1, $instance->getId());
    }
}
