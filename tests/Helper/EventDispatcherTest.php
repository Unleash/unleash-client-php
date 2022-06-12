<?php

namespace Unleash\Client\Tests\Helper;

use PHPUnit\Framework\TestCase;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Helper\EventDispatcher;

final class EventDispatcherTest extends TestCase
{
    public function testNull()
    {
        $instance = new EventDispatcher(null);

        $listener = function () {
            return true;
        };
        $subscriber = new class implements EventSubscriberInterface {
            public static function getSubscribedEvents(): array
            {
                return [];
            }
        };
        $event = new stdClass();

        $instance->addListener('test', $listener);
        $instance->addSubscriber($subscriber);
        $instance->removeListener('test', $listener);
        $instance->removeSubscriber($subscriber);
        self::assertIsArray($instance->getListeners());
        self::assertCount(0, $instance->getListeners());
        self::assertIsArray($instance->getListeners('test'));
        self::assertCount(0, $instance->getListeners('test'));
        self::assertSame($event, $instance->dispatch($event));
        self::assertSame($event, $instance->dispatch($event, 'test'));
        self::assertNull($instance->getListenerPriority('test', $listener));
        self::assertFalse($instance->hasListeners());
        self::assertFalse($instance->hasListeners('test'));
    }

    public function testInstance()
    {
        $calledCount = 0;

        $instance = new EventDispatcher(new SymfonyEventDispatcher());
        $listener = function (stdClass $event) use (&$calledCount) {
            ++$calledCount;
        };
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
                return [
                    'test2' => 'onTest',
                ];
            }

            public function onTest(stdClass $event): void
            {
                ++$this->calledCount;
            }
        };
        $event = new stdClass();

        $instance->addListener('test', $listener);
        $instance->addSubscriber($subscriber);
        self::assertCount(2, $instance->getListeners());
        self::assertCount(1, $instance->getListeners('test'));
        self::assertCount(1, $instance->getListeners('test2'));
        $instance->removeListener('test', $listener);
        self::assertCount(1, $instance->getListeners());
        $instance->removeSubscriber($subscriber);
        self::assertCount(0, $instance->getListeners());
        $instance->addSubscriber($subscriber);
        $instance->addListener('test', $listener);
        self::assertSame($event, $instance->dispatch($event));
        self::assertEquals(0, $calledCount);
        self::assertSame($event, $instance->dispatch($event, 'test'));
        self::assertEquals(1, $calledCount);
        self::assertSame($event, $instance->dispatch($event, 'test2'));
        self::assertEquals(0, $instance->getListenerPriority('test', $listener));
        self::assertNull($instance->getListenerPriority('test2', $listener));
        self::assertTrue($instance->hasListeners());
        self::assertTrue($instance->hasListeners('test'));
        self::assertTrue($instance->hasListeners('test2'));
        self::assertFalse($instance->hasListeners('test3'));
    }
}
