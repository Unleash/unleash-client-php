<?php

namespace Unleash\Client\Event;

use Symfony\Contracts\EventDispatcher\Event;

if (!class_exists(Event::class)) {
    require __DIR__ . '/../../stubs/event-dispatcher/Event.php';
}

abstract class AbstractEvent extends Event
{
}
