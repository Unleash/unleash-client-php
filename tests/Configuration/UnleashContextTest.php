<?php

namespace Rikudou\Tests\Unleash\Configuration;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;

final class UnleashContextTest extends TestCase
{
    public function testCustomProperties()
    {
        $context = new UnleashContext();

        self::assertFalse($context->hasCustomProperty('test'));

        $context->setCustomProperty('test', 'test');
        self::assertTrue($context->hasCustomProperty('test'));
        self::assertEquals('test', $context->getCustomProperty('test'));

        $context->setCustomProperty('test', 'test2');
        self::assertEquals('test2', $context->getCustomProperty('test'));

        $context->removeCustomProperty('test');
        self::assertFalse($context->hasCustomProperty('test'));
    }
}
