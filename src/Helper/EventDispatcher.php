<?php

namespace Unleash\Client\Helper;

use JetBrains\PhpStorm\ExpectedValues;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Event\UnleashEvents;

// @codeCoverageIgnoreStart
if (!interface_exists(EventDispatcherInterface::class)) {
    require_once __DIR__ . '/../../stubs/event-dispatcher/EventDispatcherInterface.php';
}
// @codeCoverageIgnoreEnd

/**
 * @internal
 */
final class EventDispatcher implements EventDispatcherInterface
{
    /**
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct(
        private ?EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        $this->eventDispatcher?->addListener($eventName, $listener, $priority);
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->eventDispatcher?->addSubscriber($subscriber);
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        $this->eventDispatcher?->removeListener($eventName, $listener);
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        $this->eventDispatcher?->removeSubscriber($subscriber);
    }

    /**
     * @phpstan-return array<callable[]|callable>
     */
    public function getListeners(string $eventName = null): array
    {
        if (is_string($eventName)) {
            return $this->eventDispatcher?->getListeners($eventName) ?? [];
        }

        return $this->eventDispatcher?->getListeners() ?? [];
    }

    public function dispatch(
        object $event,
        #[ExpectedValues(valuesFromClass: UnleashEvents::class)]
        string $eventName = null,
    ): object {
        if (is_string($eventName)) {
            $result = $this->eventDispatcher?->dispatch($event, $eventName) ?? $event;
        } else {
            $result = $this->eventDispatcher?->dispatch($event) ?? $event;
        }

        return $result;
    }

    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        return $this->eventDispatcher?->getListenerPriority($eventName, $listener);
    }

    public function hasListeners(string $eventName = null): bool
    {
        if (is_string($eventName)) {
            return $this->eventDispatcher?->hasListeners($eventName) ?? false;
        }

        return $this->eventDispatcher?->hasListeners() ?? false;
    }
}
