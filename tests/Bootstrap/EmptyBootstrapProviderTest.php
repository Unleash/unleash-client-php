<?php

namespace Unleash\Client\Tests\Bootstrap;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Bootstrap\EmptyBootstrapProvider;

final class EmptyBootstrapProviderTest extends TestCase
{
    public function testGetBootstrap()
    {
        self::assertNull((new EmptyBootstrapProvider())->getBootstrap());
    }
}
