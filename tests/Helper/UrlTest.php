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

    public function testPreexistingQueryString()
    {
        $instance = new Url('https://localhost?someQuery=someValue&someQuery2=someValue2', 'somePrefix', [
            'someTag' => 'someValue',
            'someTag2' => 'someValue2',
        ]);
        self::assertSame(
            'https://localhost?someQuery=someValue&someQuery2=someValue2&namePrefix=somePrefix&tag=someTag%3AsomeValue&tag=someTag2%3AsomeValue2',
            (string) $instance
        );
    }

    /**
     * @dataProvider appendPathData
     */
    public function testAppendPath(string $url, string $path, string $expected)
    {
        self::assertSame(
            $expected,
            (string) Url::appendPath($url, $path)
        );
    }

    public function appendPathData(): iterable
    {
        yield ['http://localhost', 'test', 'http://localhost/test'];
        yield ['http://localhost', '/test', 'http://localhost/test'];
        yield ['http://localhost/', '/test', 'http://localhost/test'];
        yield ['http://localhost/', '/test/', 'http://localhost/test/'];
        yield ['http://localhost', '', 'http://localhost'];
        yield ['http://localhost/test', '/test', 'http://localhost/test/test'];
        yield ['http://localhost/test?someQuery=someParam', '/test', 'http://localhost/test/test?someQuery=someParam'];
    }
}
