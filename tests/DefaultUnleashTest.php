<?php

namespace Unleash\Client\Tests;

use ArrayIterator;
use GuzzleHttp\Psr7\HttpFactory;
use LimitIterator;
use ReflectionMethod;
use RuntimeException;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\DTO\Feature;
use Unleash\Client\Enum\ImpressionDataEventType;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleMissingStrategyHandlerEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\ImpressionDataEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Repository\DefaultUnleashRepository;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\Tests\Traits\RealCacheImplementationTrait;
use Unleash\Client\UnleashBuilder;
use Unleash\Client\Variant\DefaultVariantHandler;

final class DefaultUnleashTest extends AbstractHttpClientTestCase
{
    use FakeCacheImplementationTrait, RealCacheImplementationTrait {
        FakeCacheImplementationTrait::getCache insteadof RealCacheImplementationTrait;
        RealCacheImplementationTrait::tearDown as clearCache;
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearCache();
    }

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

    public function testEventToggleNotFound()
    {
        $triggeredCount = 0;

        $eventDispatcher = new EventDispatcher();
        $subscriber = new class($triggeredCount) implements EventSubscriberInterface {
            /**
             * @var int
             */
            private $triggeredCount;

            public function __construct(int &$triggeredCount)
            {
                $this->triggeredCount = &$triggeredCount;
            }

            public static function getSubscribedEvents(): array
            {
                return [UnleashEvents::FEATURE_TOGGLE_NOT_FOUND => 'onNotFound'];
            }

            public function onNotFound(FeatureToggleNotFoundEvent $event)
            {
                ++$this->triggeredCount;
            }
        };

        $builder = UnleashBuilder::create()
            ->withFetchingEnabled(false)
            ->withCacheHandler($this->getCache())
            ->withEventSubscriber($subscriber)
            ->withBootstrap([
                'features' => [
                    [
                        'name' => 'test',
                        'enabled' => true,
                        'strategies' => [
                            [
                                'name' => 'default',
                            ],
                        ],
                    ],
                ],
            ]);

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->build();
        self::assertTrue($instance->isEnabled('test'));

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->build();
        self::assertFalse($instance->isEnabled('test2'));

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->build();
        self::assertFalse($instance->isEnabled('test3'));

        self::assertEquals(2, $triggeredCount);
    }

    public function testEventStrategyHandlerNotFound()
    {
        $calledCount = 0;

        $eventDispatcher = new EventDispatcher();
        $builder = UnleashBuilder::create()
            ->withFetchingEnabled(false)
            ->withCacheHandler($this->getCache())
            ->withBootstrap([
                'features' => [
                    [
                        'name' => 'test',
                        'enabled' => true,
                        'strategies' => [
                            [
                                'name' => 'unknownStrategy',
                            ],
                        ],
                    ],
                    [
                        'name' => 'test2',
                        'enabled' => true,
                        'strategies' => [
                            [
                                'name' => 'disabledStrategy',
                            ],
                        ],
                    ],
                ],
            ]);
        $subscriber = new class($calledCount) implements EventSubscriberInterface {
            /**
             * @var int
             */
            private $calledCount;

            public function __construct(int &$calledCount)
            {
                $this->calledCount = &$calledCount;
            }

            public static function getSubscribedEvents()
            {
                return [UnleashEvents::FEATURE_TOGGLE_MISSING_STRATEGY_HANDLER => 'onNoStrategyHandler'];
            }

            public function onNoStrategyHandler(FeatureToggleMissingStrategyHandlerEvent $event): void
            {
                ++$this->calledCount;
            }
        };

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->withEventSubscriber($subscriber)->build();
        // check that nonexistent features won't trigger the event
        $instance->isEnabled('test3');
        self::assertSame(0, $calledCount);
        $instance->isEnabled('test2');
        self::assertSame(1, $calledCount);
        $instance->isEnabled('test');
        self::assertSame(2, $calledCount);
    }

    public function testEventDisabledFeature()
    {
        $calledCount = 0;
        $subscriber = new class($calledCount) implements EventSubscriberInterface {
            /**
             * @var int
             */
            private $calledCount;

            public function __construct(int &$calledCount)
            {
                $this->calledCount = &$calledCount;
            }

            public static function getSubscribedEvents(): array
            {
                return [UnleashEvents::FEATURE_TOGGLE_DISABLED => 'onDisabled'];
            }

            public function onDisabled(FeatureToggleDisabledEvent $event): void
            {
                ++$this->calledCount;
            }
        };

        $unleash = UnleashBuilder::create()
            ->withCacheHandler($this->getCache())
            ->withFetchingEnabled(false)
            ->withEventSubscriber($subscriber)
            ->withBootstrap([
                'features' => [
                    [
                        'name' => 'test',
                        'enabled' => false,
                        'strategies' => [
                            [
                                'name' => 'default',
                            ],
                        ],
                    ],
                    [
                        'name' => 'test2',
                        'enabled' => false,
                        'strategies' => [
                            [
                                'name' => 'default',
                            ],
                        ],
                    ],
                ],
            ])
            ->build();

        self::assertFalse($unleash->isEnabled('test'));
        self::assertFalse($unleash->isEnabled('test2'));
        self::assertFalse($unleash->isEnabled('test3'));

        self::assertSame(2, $calledCount);
    }

    public function testImpressionData()
    {
        $triggeredCount = 0;

        $dispatcher = new EventDispatcher();

        $dispatcher->addListener(UnleashEvents::IMPRESSION_DATA, function (ImpressionDataEvent $event) use (&$triggeredCount) {
            // trigger these to have complete code coverage
            $event->getEventId();
            $event->getContext();
            $event->jsonSerialize();

            if ($event->getFeatureName() === 'test') {
                self::assertSame(ImpressionDataEventType::IS_ENABLED, $event->getEventType());
                self::assertFalse($event->isEnabled());
                self::assertNull($event->getVariant());
            } else {
                self::assertTrue($event->isEnabled());
                if ($event->getEventType() === ImpressionDataEventType::GET_VARIANT) {
                    self::assertNotNull($event->getVariant());
                }
            }

            ++$triggeredCount;
        });

        $instance = UnleashBuilder::create()
            ->withCacheHandler($this->getCache())
            ->withEventDispatcher($dispatcher)
            ->withFetchingEnabled(false)
            ->withBootstrap([
                'version' => 1,
                'features' => [
                    [
                        'name' => 'test',
                        'description' => '',
                        'enabled' => false,
                        'impressionData' => true,
                        'strategies' => [
                            [
                                'name' => 'default',
                            ],
                        ],
                    ],
                    [
                        'name' => 'test2',
                        'description' => '',
                        'enabled' => true,
                        'impressionData' => true,
                        'strategies' => [
                            [
                                'name' => 'default',
                            ],
                        ],
                        'variants' => [
                            [
                                'name' => 'variant1',
                                'weight' => 1,
                                'payload' => [
                                    'type' => 'string',
                                    'value' => 'val1',
                                ],
                            ],
                        ],
                    ],
                ],
            ])
            ->build();

        $instance->isEnabled('nonexistent');
        self::assertSame(0, $triggeredCount);

        $instance->isEnabled('test');
        self::assertSame(1, $triggeredCount);

        $instance->getVariant('test');
        self::assertSame(1, $triggeredCount);

        $instance->getVariant('test2');
        self::assertSame(2, $triggeredCount);
    }

    public function testAutoRegistrationOnInvalidResponse()
    {
        $this->pushResponse(new RuntimeException("This exception shouldn't be propagated"), 1, 404);
        new DefaultUnleash(
            [new DefaultStrategyHandler()],
            $this->repository,
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setCache($this->getCache()),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator())
        );
    }

    public function testDependenciesUnresolvedFeatureVariants()
    {
        $data = [
            'features' => [
                [
                    'name' => 'child',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                        ],
                    ],
                    'dependencies' => [
                        [
                            'feature' => 'parent',
                            'variants' => ['variant1'],
                        ],
                    ],
                ],
                [
                    'name' => 'parent',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                        ],
                    ],
                    'variants' => [
                        [
                            'name' => 'variant1',
                            'weight' => 1,
                        ],
                    ],
                ],
            ],
        ];
        $this->pushResponse($data);
        $instance = $this->getCachedInstance();
        self::assertTrue($instance->isEnabled('child'));
    }

    public function testDependenciesDirectFeatureVariants()
    {
        $data = [
            'features' => [
                [
                    'name' => 'parent',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                        ],
                    ],
                    'variants' => [
                        [
                            'name' => 'variant1',
                            'weight' => 1,
                        ],
                    ],
                ],
                [
                    'name' => 'child',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                        ],
                    ],
                    'dependencies' => [
                        [
                            'feature' => 'parent',
                            'variants' => ['variant1'],
                        ],
                    ],
                ],
            ],
        ];
        $this->pushResponse($data);
        $instance = $this->getCachedInstance();
        self::assertTrue($instance->isEnabled('child'));
    }

    public function testDependenciesMixedResolvedUnresolved()
    {
        $data = [
            'features' => [
                [
                    'name' => 'parent2',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                        ],
                    ],
                    'variants' => [
                        [
                            'name' => 'variant1',
                            'weight' => 1,
                        ],
                    ],
                ],
                [
                    'name' => 'child',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                        ],
                    ],
                    'dependencies' => [
                        [
                            'feature' => 'parent1',
                            'variants' => ['variant0', 'variant1'],
                        ],
                        [
                            'feature' => 'parent2',
                        ],
                    ],
                ],
                [
                    'name' => 'parent1',
                    'enabled' => true,
                    'strategies' => [
                        [
                            'name' => 'default',
                        ],
                    ],
                    'variants' => [
                        [
                            'name' => 'variant1',
                            'weight' => 1,
                        ],
                    ],
                ],
            ],
        ];
        $this->pushResponse($data);
        $instance = $this->getCachedInstance();
        self::assertTrue($instance->isEnabled('child'));
    }

    public function testFeatureWithoutOptionalMethods()
    {
        $feature = new class implements Feature {
            public function getName(): string
            {
                return 'test';
            }

            public function isEnabled(): bool
            {
                return true;
            }

            public function getStrategies(): iterable
            {
                return [new DefaultStrategy('default')];
            }

            public function getVariants(): array
            {
                return [];
            }

            public function hasImpressionData(): bool
            {
                return false;
            }
        };

        $unleash = $this->getInstance(new DefaultStrategyHandler());
        $isEnabled = new ReflectionMethod($unleash, 'isFeatureEnabled');

        self::assertTrue($isEnabled->getClosure($unleash)($feature, new UnleashContext())->isEnabled());
    }

    public function testIsFeatureEnabled()
    {
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
                        ],
                    ],
                ],
            ],
        ]);
        $instance = $this->getInstance(new DefaultStrategyHandler());
        $variant = $instance->getVariant('test');
        self::assertTrue($variant->isFeatureEnabled());

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
                        ],
                    ],
                ],
            ],
        ]);
        $instance = $this->getInstance(new DefaultStrategyHandler());
        $variant = $instance->getVariant('test');
        self::assertFalse($variant->isFeatureEnabled());
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

    private function getCachedInstance(): DefaultUnleash
    {
        return new DefaultUnleash(
            [
                new DefaultStrategyHandler(),
            ],
            new DefaultUnleashRepository(
                $this->httpClient,
                new HttpFactory(),
                (new UnleashConfiguration('', '', ''))
                    ->setAutoRegistrationEnabled(false)
                    ->setCache($this->getFreshCacheInstance())
                    ->setTtl(30)
            ),
            $this->registrationService,
            (new UnleashConfiguration('', '', ''))
                ->setAutoRegistrationEnabled(false)
                ->setCache($this->getFreshCacheInstance())
                ->setTtl(30),
            $this->metricsHandler,
            new DefaultVariantHandler(new MurmurHashCalculator())
        );
    }
}
