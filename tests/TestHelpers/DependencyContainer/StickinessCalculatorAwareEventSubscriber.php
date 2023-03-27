<?php

namespace Unleash\Client\Tests\TestHelpers\DependencyContainer;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Unleash\Client\Helper\Builder\StickinessCalculatorAware;
use Unleash\Client\Stickiness\StickinessCalculator;

final class StickinessCalculatorAwareEventSubscriber implements EventSubscriberInterface, StickinessCalculatorAware
{
    /**
     * @var StickinessCalculator|null
     */
    public $stickinessCalculator = null;

    public static function getSubscribedEvents(): array
    {
        return [];
    }

    public function setStickinessCalculator(StickinessCalculator $stickinessCalculator): void
    {
        $this->stickinessCalculator = $stickinessCalculator;
    }
}
