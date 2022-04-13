<?php

namespace Unleash\Client\Tests;

use ArrayIterator;
use LimitIterator;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\DTO\Feature;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Variant\DefaultVariantHandler;

final class DefaultUnleashTest extends AbstractHttpClientTest
{
    use FakeCacheImplementationTrait;

    public function testIsEnabled()
    {
        $instance = $this->getInstance(new DefaultStrategyHandler());

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => false,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'parameters' => [],
                        ],
                    ],
                ],
            ],
        ], 2);
        self::assertFalse($instance->isEnabled('test'));
        self::assertFalse($instance->isEnabled('test', null, true));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [],
                ],
            ],
        ]);
        self::assertTrue($instance->isEnabled('test'));
    }

    public function testIsEnabledDefault()
    {
        $instance = $this->getInstance(new DefaultStrategyHandler());

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'parameters' => [],
                        ],
                    ],
                ],
            ],
        ], 3);

        self::assertTrue($instance->isEnabled('test'));
        self::assertFalse($instance->isEnabled('test2'));
        self::assertTrue($instance->isEnabled('test3', null, true));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'userWithId',
                            'parameters' => [
                                'userIds' => 'test,test2',
                            ],
                        ],
                    ],
                ],
            ],
        ], 3);
        self::assertFalse($instance->isEnabled('test'));
        self::assertFalse($instance->isEnabled('test2'));
        self::assertTrue($instance->isEnabled('test3', null, true));
    }

    public function testIsEnabledIpAddress()
    {
        $instance = $this->getInstance(new IpAddressStrategyHandler());
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '127.0.0.1,192.168.0.1',
                            ],
                        ],
                    ],
                ],
            ],
        ], 3);

        self::assertTrue($instance->isEnabled('test'));
        self::assertFalse($instance->isEnabled('test2'));
        self::assertTrue($instance->isEnabled('test3', null, true));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'parameters' => [],
                        ],
                    ],
                ],
            ],
        ]);
        self::assertFalse($instance->isEnabled('test'));

        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '127.0.0.1,192.168.0.1',
                            ],
                        ],
                    ],
                ],
            ],
        ], 2);
        self::assertFalse($instance->isEnabled('test'));
        $context = new UnleashContext(null, '192.168.0.1');
        self::assertTrue($instance->isEnabled('test', $context));
    }

    public function testIsEnabledIpAdressCidr()
    {
        $instance = $this->getInstance(new IpAddressStrategyHandler());

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '192.168.0.0/16',
                            ],
                        ],
                    ],
                ],
            ],
        ], 3);
        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '192.168', // invalid ip
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $_SERVER['REMOTE_ADDR'] = '192.168.86.50';
        self::assertTrue($instance->isEnabled('test'));
        $_SERVER['REMOTE_ADDR'] = '192.168.0.1';
        self::assertTrue($instance->isEnabled('test'));
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        self::assertFalse($instance->isEnabled('test'));
        self::assertFalse($instance->isEnabled('test'));
    }

    public function testIsEnabledUserId()
    {
        $instance = $this->getInstance(new UserIdStrategyHandler());
        $context = new UnleashContext('123');

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'userWithId',
                            'parameters' => [
                                'userIds' => 'test,test2,123',
                            ],
                        ],
                    ],
                ],
            ],
        ], 3);
        self::assertTrue($instance->isEnabled('test', $context));
        self::assertFalse($instance->isEnabled('test2', $context));
        self::assertTrue($instance->isEnabled('test3', $context, true));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                            'parameters' => [],
                        ],
                    ],
                ],
            ],
        ]);
        self::assertFalse($instance->isEnabled('test'));
    }

    public function testIsEnabledGradual()
    {
        $instance = $this->getInstance(new GradualRolloutStrategyHandler(new MurmurHashCalculator()));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'flexibleRollout',
                            'parameters' => [
                                'groupId' => 'default',
                                'rollout' => 100,
                                'stickiness' => 'DEFAULT',
                            ],
                        ],
                    ],
                ],
            ],
        ], 3);

        self::assertTrue($instance->isEnabled('test'));
        self::assertFalse($instance->isEnabled('test2'));
        self::assertTrue($instance->isEnabled('test3', null, true));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'flexibleRollout',
                            'parameters' => [
                                'groupId' => 'default',
                                'rollout' => 50,
                                'stickiness' => 'DEFAULT',
                            ],
                        ],
                    ],
                ],
            ],
        ], 4);

        $contextTrue = new UnleashContext('634');
        $contextFalse = new UnleashContext('123');

        self::assertTrue($instance->isEnabled('test', $contextTrue));
        self::assertFalse($instance->isEnabled('test', $contextFalse));

        $contextTrue = new UnleashContext(null, null, '634');
        $contextFalse = new UnleashContext(null, null, '123');

        self::assertTrue($instance->isEnabled('test', $contextTrue));
        self::assertFalse($instance->isEnabled('test', $contextFalse));
    }

    public function testIsEnabledMultiple()
    {
        $instance = $this->getInstance(
            new DefaultStrategyHandler(),
            new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler()
        );
        $_SERVER['REMOTE_ADDR'] = '1.2.3.4';

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '127.0.0.1,192.168.0.1',
                            ],
                        ],
                        [
                            'name' => 'userWithId',
                            'parameters' => [
                                'userIds' => 'test,test2,123',
                            ],
                        ],
                        [
                            'name' => 'flexibleRollout',
                            'parameters' => [
                                'groupId' => 'default',
                                'rollout' => 50,
                                'stickiness' => 'DEFAULT',
                            ],
                        ],
                        [
                            'name' => 'default',
                            'parameters' => [],
                        ],
                    ],
                ],
            ],
        ]);
        $context = new UnleashContext('852', '192.168.0.1', '852');
        self::assertTrue($instance->isEnabled('test', $context));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '127.0.0.1,192.168.0.1',
                            ],
                        ],
                        [
                            'name' => 'userWithId',
                            'parameters' => [
                                'userIds' => 'test,test2,123',
                            ],
                        ],
                        [
                            'name' => 'flexibleRollout',
                            'parameters' => [
                                'groupId' => 'default',
                                'rollout' => 50,
                                'stickiness' => 'DEFAULT',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        self::assertTrue($instance->isEnabled('test', $context));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '127.0.0.1,192.168.0.1',
                            ],
                        ],
                        [
                            'name' => 'userWithId',
                            'parameters' => [
                                'userIds' => 'test,test2,123',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($instance->isEnabled('test', $context));

        $this->pushResponse([
            'version' => 1,
            'features' => [
                [
                    'name' => 'test',
                    'description' => '',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'remoteAddress',
                            'parameters' => [
                                'IPs' => '127.0.0.1,192.168.0.1',
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        self::assertTrue($instance->isEnabled('test', $context));
    }

    public function testRegister()
    {
        $instance = $this->getInstance();
        $this->pushResponse([]);
        self::assertTrue($instance->register());
        $this->pushResponse([], 1, 400);
        self::assertFalse($instance->register());

        $this->pushResponse([]);
        new DefaultUnleash(
            [],
            $this->repository,
            $this->registrationService,
            new UnleashConfiguration('', '', ''),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator())
        );
        self::assertCount(3, $this->requestHistory);
    }

    public function testIterators()
    {
        $repository = new class implements UnleashRepository {
            private $cache = [];

            public function findFeature(string $featureName): ?Feature
            {
                if (!isset($this->cache[$featureName])) {
                    $this->cache[$featureName] = new DefaultFeature(
                        $featureName,
                        true,
                        new LimitIterator(new ArrayIterator([new DefaultStrategy('default')]))
                    );
                }

                return $this->cache[$featureName];
            }

            public function getFeatures(): iterable
            {
                return [];
            }
        };

        $instance = new DefaultUnleash(
            new ArrayIterator([new DefaultStrategyHandler()]),
            $repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setAutoRegistrationEnabled(false)
                ->setCache($this->getCache()),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator())
        );
        self::assertTrue($instance->isEnabled('someFeature'));
        self::assertTrue($instance->isEnabled('someFeature'));
    }

    private function getInstance(StrategyHandler ...$handlers): DefaultUnleash
    {
        return new DefaultUnleash(
            $handlers,
            $this->repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setAutoRegistrationEnabled(false)
                ->setCache($this->getCache()),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator())
        );
    }
}
