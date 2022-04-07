<?php

namespace Unleash\Client\Tests\ConstraintValidator\Operator\Lists;

use PHPUnit\Framework\TestCase;
use Unleash\Client\ConstraintValidator\Operator\Lists\AbstractListOperatorValidator;

final class AbstractListOperatorValidatorTest extends TestCase
{
    public function testAcceptsValues()
    {
        $instance = new class extends AbstractListOperatorValidator {
            protected function validate(string $currentValue, $searchInValue): bool
            {
                return false;
            }

            public function acceptsValues($values): bool
            {
                return parent::acceptsValues($values);
            }
        };

        self::assertTrue($instance->acceptsValues([1, 2, 3]));
        self::assertTrue($instance->acceptsValues(['test1', 'test2', 'test3']));
        self::assertTrue($instance->acceptsValues([0 => 'test1', 1 => 'test2', 2 => 'test3']));

        self::assertFalse($instance->acceptsValues([1 => 1, 2 => 2, 3 => 3]));
        self::assertFalse($instance->acceptsValues(['test1' => 'test1', 'test2' => 'test2', 'test3' => 'test3']));
    }
}
