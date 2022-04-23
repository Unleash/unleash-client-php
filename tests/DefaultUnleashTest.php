<?php

namespace Unleash\Client\Tests;

use ArrayIterator;
use LimitIterator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DefaultUnleash;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultStrategy;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\Feature;
use Unleash\Client\DTO\Strategy;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleNoStrategyHandlerEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Event\FeatureVariantBeforeFallbackReturnedEvent;
use Unleash\Client\Event\UnleashEvents;
use Unleash\Client\Repository\UnleashRepository;
use Unleash\Client\Stickiness\MurmurHashCalculator;
use Unleash\Client\Strategy\DefaultStrategyHandler;
use Unleash\Client\Strategy\GradualRolloutStrategyHandler;
use Unleash\Client\Strategy\IpAddressStrategyHandler;
use Unleash\Client\Strategy\StrategyHandler;
use Unleash\Client\Strategy\UserIdStrategyHandler;
use Unleash\Client\Tests\Traits\FakeCacheImplementationTrait;
use Unleash\Client\UnleashBuilder;
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

    public function testEventToggleNotFound()
    {
        $triggeredCount = 0;

        $eventDispatcher = new EventDispatcher();
        $enabledFactory = function (?bool $result) use (&$triggeredCount) {
            return new class($result, $triggeredCount) implements EventSubscriberInterface {
                /**
                 * @var bool|null
                 */
                private $result;

                /**
                 * @var int
                 */
                private $triggeredCount;

                public function __construct(?bool $result, int &$triggeredCount)
                {
                    $this->result = $result;
                    $this->triggeredCount = &$triggeredCount;
                }

                public static function getSubscribedEvents(): array
                {
                    return [UnleashEvents::FEATURE_TOGGLE_NOT_FOUND => 'onNotFound'];
                }

                public function onNotFound(FeatureToggleNotFoundEvent $event)
                {
                    ++$this->triggeredCount;
                    if ($this->result !== null) {
                        $event->setEnabled($this->result);
                    }
                }
            };
        };
        $enabledTrue = $enabledFactory(true);
        $enabledFalse = $enabledFactory(false);
        $enabledNull = $enabledFactory(null);

        $builder = UnleashBuilder::create()
            ->withFetchingEnabled(false)
            ->withCacheHandler($this->getCache())
            ->withBootstrap([
                'features' => [],
            ]);

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->withEventSubscriber($enabledTrue)->build();
        self::assertTrue($instance->isEnabled('test'));

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->withEventSubscriber($enabledFalse)->build();
        self::assertFalse($instance->isEnabled('test'));

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->withEventSubscriber($enabledNull)->build();
        self::assertFalse($instance->isEnabled('test'));

        self::assertEquals(3, $triggeredCount);

        $featureHandler = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents()
            {
                return [UnleashEvents::FEATURE_TOGGLE_NOT_FOUND => 'onNotFound'];
            }

            public function onNotFound(FeatureToggleNotFoundEvent $event)
            {
                $context = $event->getContext();
                if (
                    !$context->findContextValue('disabled')
                    && $event->getFeatureName() !== 'disabled'
                ) {
                    $event->setFeature(new DefaultFeature(
                        'test',
                        true,
                        [new DefaultStrategy('default')],
                    ));
                }
            }
        };
        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->withEventSubscriber($featureHandler)->build();
        self::assertTrue($instance->isEnabled('test'));
        // the feature is overridden manually in event so regardless of name it should return true
        self::assertTrue($instance->isEnabled('test2'));
        // check that passing context works
        self::assertFalse(
            $instance->isEnabled(
                'test',
                (new UnleashContext())->setCustomProperty('disabled', 'yes')
            ),
        );
        // check that passing feature name works
        self::assertFalse($instance->isEnabled('disabled'));
    }

    public function testEventStrategyHandlerNotFound()
    {
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
        $subscriber = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents()
            {
                return [UnleashEvents::FEATURE_TOGGLE_NO_STRATEGY_HANDLER => 'onNoStrategyHandler'];
            }

            public function onNoStrategyHandler(FeatureToggleNoStrategyHandlerEvent $event): void
            {
                $strategyNames = array_map(
                    fn (Strategy $strategy) => $strategy->getName(),
                    $event->getFeature()->getStrategies(),
                );
                if (in_array('disabledStrategy', $strategyNames, true)) {
                    return;
                }

                if ($event->getContext()->findContextValue('ignore')) {
                    return;
                }

                $event->setStrategyHandler(new DefaultStrategyHandler());
            }
        };

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->withEventSubscriber($subscriber)->build();
        // check that nonexistent features won't trigger the event
        self::assertFalse($instance->isEnabled('test3'));
        // check that event gets triggered and default strategy gets injected
        self::assertTrue($instance->isEnabled('test'));
        // check that the event is correctly provided the feature object
        self::assertFalse($instance->isEnabled('test2'));
        self::assertFalse(
            $instance->isEnabled(
                'test',
                (new UnleashContext())->setCustomProperty('ignore', 'yes')
            )
        );
    }

    public function testEventFallbackVariant()
    {
        $eventDispatcher = new EventDispatcher();
        $builder = UnleashBuilder::create()
            ->withFetchingEnabled(false)
            ->withCacheHandler($this->getCache())
            ->withBootstrap([
                'features' => [
                    [
                        'name' => 'noVariants',
                        'enabled' => true,
                        'strategies' => [
                            [
                                'name' => 'default',
                            ],
                        ],
                    ],
                    [
                        'name' => 'disabled',
                        'enabled' => false,
                        'strategies' => [
                            [
                                'name' => 'default',
                            ],
                        ],
                    ],
                    [
                        'name' => 'ignored',
                        'enabled' => true,
                        'strategies' => [],
                    ],
                ],
            ]);

        $subscriber = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [UnleashEvents::FEATURE_VARIANT_BEFORE_FALLBACK_RETURNED => 'beforeFallback'];
            }

            public function beforeFallback(FeatureVariantBeforeFallbackReturnedEvent $event): void
            {
                $feature = $event->getFeature();
                $context = $event->getContext();

                if ($feature !== null && $feature->getName() === 'ignored') {
                    return;
                }
                if ($event->getFeatureName() === 'ignored2') {
                    return;
                }
                if ($context->findContextValue('ignored')) {
                    return;
                }
                $event->setFallbackVariant(new DefaultVariant('test', true));
            }
        };

        $instance = $builder->withEventDispatcher(clone $eventDispatcher)->withEventSubscriber($subscriber)->build();
        self::assertTrue($instance->getVariant('nonexistent')->isEnabled());
        self::assertTrue($instance->getVariant('noVariants')->isEnabled());
        self::assertTrue($instance->getVariant('disabled')->isEnabled());
        self::assertFalse($instance->getVariant('ignored')->isEnabled());
        self::assertFalse($instance->getVariant(
            'noVariants',
            (new UnleashContext())->setCustomProperty('ignored', 'yes')
        )->isEnabled());
        self::assertFalse($instance->getVariant('ignored2')->isEnabled());
    }

    public function testEventDisabledFeature()
    {
        $subscriber = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [UnleashEvents::FEATURE_TOGGLE_DISABLED => 'onDisabled'];
            }

            public function onDisabled(FeatureToggleDisabledEvent $event): void
            {
                if ($event->getContext()->findContextValue('ignored')) {
                    return;
                }
                $feature = $event->getFeature();
                if ($feature->getName() === 'ignored') {
                    return;
                }

                $newFeature = new DefaultFeature(
                    $feature->getName(),
                    true,
                    $feature->getStrategies(),
                    $feature->getVariants()
                );
                $event->setFeature($newFeature);
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
                        'name' => 'ignored',
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

        self::assertTrue($unleash->isEnabled('test'));
        self::assertFalse($unleash->isEnabled('ignored'));
        self::assertFalse($unleash->isEnabled(
            'test',
            (new UnleashContext())->setCustomProperty('ignored', 'yes')
        ));
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
