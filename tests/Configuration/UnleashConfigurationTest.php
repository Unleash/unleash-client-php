<?php

namespace Rikudou\Tests\Unleash\Configuration;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashConfiguration;

final class UnleashConfigurationTest extends TestCase
{
    public function testConstructor()
    {
        $instance = new UnleashConfiguration('https://www.example.com/test', '', '');
        self::assertEquals('https://www.example.com/test/', $instance->getUrl());
    }

    public function testSetUrl()
    {
        $instance = new UnleashConfiguration('', '', '');
        $instance->setUrl('https://www.example.com/test');
        self::assertEquals('https://www.example.com/test/', $instance->getUrl());
    }
}
