<?php

namespace Unleash\Client\Tests\Helper;

use PHPUnit\Framework\TestCase;
use ReflectionObject;
use Unleash\Client\Helper\NetworkCalculator;

final class NetworkCalculatorTest extends TestCase
{
    /**
     * @dataProvider fromStringData
     */
    public function testFromString(string $cidr, string $ipAddress, int $networkSize)
    {
        $calculator = NetworkCalculator::fromString($cidr);
        self::assertEquals($ipAddress, $this->getIpAddress($calculator));
        self::assertEquals($networkSize, $this->getNetworkSize($calculator));
    }

    /**
     * @dataProvider isInRangeData
     */
    public function testIsInRange(string $cidr, string $ip, bool $result)
    {
        $not = $result ? '' : ' not';

        $calculator = NetworkCalculator::fromString($cidr);
        self::assertEquals(
            $result,
            $calculator->isInRange($ip),
            "Failed asserting that {$ip} is{$not} in CIDR {$cidr}",
        );
    }

    private function fromStringData(): array
    {
        return [
            ['127.0.0.1', '127.0.0.1', 32],
            ['192.168.0.0/8', '192.168.0.0', 8],
        ];
    }

    private function isInRangeData(): array
    {
        return [
            ['127.0.0.1', '127.0.0.1', true],
            ['127.0.0.1', '127.0.0.2', false],

            ['127.0.0.1/32', '127.0.0.1', true],
            ['127.0.0.1/32', '127.0.0.2', false],

            ['127.0.0.1/31', '127.0.0.1', true],
            ['127.0.0.1/31', '127.0.0.0', true],
            ['127.0.0.1/31', '127.0.0.2', false],

            ['127.0.0.1/30', '127.0.0.0', true],
            ['127.0.0.1/30', '127.0.0.1', true],
            ['127.0.0.1/30', '127.0.0.2', true],
            ['127.0.0.1/30', '127.0.0.3', true],
            ['127.0.0.1/30', '127.0.0.4', false],

            ['127.0.0.1/29', '127.0.0.0', true],
            ['127.0.0.1/29', '127.0.0.7', true],
            ['127.0.0.1/29', '127.0.0.8', false],

            ['192.168.86.0/17', '192.168.0.0', true],
            ['192.168.86.0/17', '192.168.127.255', true],
            ['192.168.86.0/17', '192.168.128.0', false],

            ['192.168.86.0/16', '192.168.0.0', true],
            ['192.168.86.0/16', '192.168.255.255', true],
        ];
    }

    private function getIpAddress(NetworkCalculator $calculator): string
    {
        $reflection = new ReflectionObject($calculator);
        $property = $reflection->getProperty('ipAddress');
        $property->setAccessible(true);

        return $property->getValue($calculator);
    }

    private function getNetworkSize(NetworkCalculator $calculator): int
    {
        $reflection = new ReflectionObject($calculator);
        $property = $reflection->getProperty('networkSize');
        $property->setAccessible(true);

        return $property->getValue($calculator);
    }
}
