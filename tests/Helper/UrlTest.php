<?php

namespace Unleash\Client\Tests\Helper;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Helper\Url;

final class UrlTest extends TestCase
{
    public function testUrlOnly()
    {
        $url = 'https://localhost';
        $instance = new Url($url);
        self::assertEquals($url, (string) $instance);
    }

    public function testPrefix()
    {
        $instance = new Url('https://localhost', 'somePrefix');
        self::assertEquals('https://localhost?namePrefix=somePrefix', (string) $instance);

        $instance = new Url('https://localhost', '');
        self::assertEquals('https://localhost', (string) $instance);
    }

    public function testTags()
    {
        $instance = new Url('https://localhost', null, [
            'someTag' => 'someValue',
        ]);
        self::assertEquals('https://localhost?tag=someTag%3AsomeValue', (string) $instance);

        $instance = new Url('https://localhost', null, [
            'someTag' => 'someValue',
            'someTag2' => 'someValue2',
        ]);
        self::assertEquals('https://localhost?tag=someTag%3AsomeValue&tag=someTag2%3AsomeValue2', (string) $instance);
    }

    public function testCombined()
    {
        $instance = new Url('https://localhost', 'somePrefix', [
            'someTag' => 'someValue',
            'someTag2' => 'someValue2',
        ]);
        self::assertEquals(
            'https://localhost?namePrefix=somePrefix&tag=someTag%3AsomeValue&tag=someTag2%3AsomeValue2',
            (string) $instance
        );
    }
}
