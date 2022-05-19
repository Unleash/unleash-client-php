<?php

namespace Unleash\Client\Helper;

use InvalidArgumentException;
use JetBrains\PhpStorm\ExpectedValues;
use Symfony\Component\EventDispatcher\EventDispatcher as SymfonyEventDispatcher;
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
     * @var SymfonyEventDispatcher|null
     * @readonly
     */
    private ?object $eventDispatcher;
    /**
     * @param SymfonyEventDispatcher|null $eventDispatcher
     * @noinspection PhpDocSignatureInspection
     */
    public function __construct(?object $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        if ($this->eventDispatcher !== null && !$this->eventDispatcher instanceof SymfonyEventDispatcher) { // @phpstan-ignore-line
            throw new InvalidArgumentException('The dispatcher must either be null or an instance of ' . SymfonyEventDispatcher::class);
        }
    }
    public function addListener(string $eventName, callable $listener, int $priority = 0): void
    {
        ($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->addListener($eventName, $listener, $priority) : null;
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): void
    {
        ($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->addSubscriber($subscriber) : null;
    }

    public function removeListener(string $eventName, callable $listener): void
    {
        ($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->removeListener($eventName, $listener) : null;
    }

    public function removeSubscriber(EventSubscriberInterface $subscriber): void
    {
        ($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->removeSubscriber($subscriber) : null;
    }

    /**
     * @phpstan-return array<callable[]|callable>
     */
    public function getListeners(string $eventName = null): array
    {
        if (is_string($eventName)) {
            return (($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->getListeners($eventName) : null) ?? [];
        }

        return (($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->getListeners() : null) ?? [];
    }

    public function dispatch(object $event, #[ExpectedValues(valuesFromClass: UnleashEvents::class)]
    string $eventName = null) : object
    {
        if (is_string($eventName)) {
            $result = (($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->dispatch($event, $eventName) : null) ?? $event;
        } else {
            $result = (($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->dispatch($event) : null) ?? $event;
        }
        return $result;
    }

    public function getListenerPriority(string $eventName, callable $listener): ?int
    {
        return ($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->getListenerPriority($eventName, $listener) : null;
    }

    public function hasListeners(string $eventName = null): bool
    {
        if (is_string($eventName)) {
            return (($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->hasListeners($eventName) : null) ?? false;
        }

        return (($eventDispatcher = $this->eventDispatcher) ? $eventDispatcher->hasListeners() : null) ?? false;
    }
}
