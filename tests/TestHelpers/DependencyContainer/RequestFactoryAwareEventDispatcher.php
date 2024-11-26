<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Psr\Http\Message\RequestFactoryInterface;
use stdClass;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Helper\Builder\RequestFactoryAware;

final class RequestFactoryAwareEventDispatcher implements EventDispatcherInterface, RequestFactoryAware
{
    /**
     * @var RequestFactoryInterface|null
     */
    public $requestFactory = null;

    public function dispatch(object $event, ?string $eventName = null): object
    {
        return new stdClass();
    }

    public function setRequestFactory(RequestFactoryInterface $requestFactory): void
    {
        $this->requestFactory = $requestFactory;
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
    }

    public function removeListener(string $eventName, callable $listener): void
    {
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
    }

    public function getListeners(?string $eventName = null): array
    {
        return [];
    }

    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        return null;
    }

    public function hasListeners(?string $eventName = null): bool
    {
        return false;
    }
}
