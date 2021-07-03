<?php

namespace Rikudou\Tests\Unleash;

use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DefaultUnleash;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Strategy\DefaultStrategyHandler;
use Rikudou\Unleash\Strategy\GradualRolloutStrategyHandler;
use Rikudou\Unleash\Strategy\IpAddressStrategyHandler;
use Rikudou\Unleash\Strategy\UserIdStrategyHandler;

final class DefaultUnleashTest extends AbstractHttpClientTest
{
    public function testIsEnabled()
    {
        $instance = new DefaultUnleash([
            new DefaultStrategyHandler(),
        ], $this->repository, $this->registrationService);

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
        $instance = new DefaultUnleash([
            new DefaultStrategyHandler(),
        ], $this->repository, $this->registrationService);

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
        $instance = new DefaultUnleash([
            new IpAddressStrategyHandler(),
        ], $this->repository, $this->registrationService);
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

    public function testIsEnabledUserId()
    {
        $instance = new DefaultUnleash([
            new UserIdStrategyHandler(),
        ], $this->repository, $this->registrationService);
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
        $instance = new DefaultUnleash([
            new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
        ], $this->repository, $this->registrationService);

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
        $instance = new DefaultUnleash([
            new DefaultStrategyHandler(),
            new GradualRolloutStrategyHandler(new MurmurHashCalculator()),
            new IpAddressStrategyHandler(),
            new UserIdStrategyHandler(),
        ], $this->repository, $this->registrationService);
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
}
