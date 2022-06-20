<?php

namespace Unleash\Client\Event;

use Symfony\Contracts\EventDispatcher\Event;

// @codeCoverageIgnoreStart
if (!class_exists(Event::class)) {
    require __DIR__ . '/../../stubs/event-dispatcher/Event.php';
}
// @codeCoverageIgnoreEnd

abstract class AbstractEvent extends Event
{
}
