<?php

namespace Stickiness;

use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;

final class MurmurHashCalculatorTest extends TestCase
{
    public function testCalculate()
    {
        $instance = new MurmurHashCalculator();
        self::assertEquals(0, $instance->calculate('', 'default'));
        self::assertEquals(0, $instance->calculate('0', 'default'));
    }
}
