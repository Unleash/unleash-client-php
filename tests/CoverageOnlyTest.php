<?php

namespace Unleash\Client\Tests;

use PHPUnit\Framework\TestCase;
use Unleash\Client\Configuration\UnleashConfiguration;
use Unleash\Client\Configuration\UnleashContext;
use Unleash\Client\DTO\DefaultFeature;
use Unleash\Client\DTO\DefaultSegment;
use Unleash\Client\DTO\DefaultVariant;
use Unleash\Client\DTO\DefaultVariantPayload;
use Unleash\Client\Enum\VariantPayloadType;
use Unleash\Client\Event\FeatureToggleDisabledEvent;
use Unleash\Client\Event\FeatureToggleMissingStrategyHandlerEvent;
use Unleash\Client\Event\FeatureToggleNotFoundEvent;
use Unleash\Client\Exception\CompoundException;

/**
 * This class is only for triggering code that doesn't really make sense to test and is here to achieve 100% code coverage.
 * The reason is to catch potential problems during transpilation to lower versions of php.
 */
final class CoverageOnlyTest extends TestCase
{
    /**
     * For whatever reason PHPUnit doesn't include tests that don't perform assertions when calculating
     * code coverage, so here's one dumb assertion that will get triggered after every test.
     */
    protected function tearDown(): void
    {
        self::assertTrue(true);
    }

    public function testDefaultSegment(): void
    {
        $instance = new DefaultSegment(1, []);
        $instance->getId();
    }

    public function testUnleashConfiguration(): void
    {
        $instance = new UnleashConfiguration('', '', '');
        $instance->setAppName('test');
        $instance->setInstanceId('test');
    }

    public function testUnleashContext(): void
    {
        $instance = new UnleashContext();
        $instance->getCustomProperties();
    }

    public function testDefaultVariant(): void
    {
        $instance = new DefaultVariant('test', true);
        $instance->getPayload();
    }

    public function testDefaultVariantPayload(): void
    {
        $instance = new DefaultVariantPayload(VariantPayloadType::STRING, 'test');
        $instance->getType();
    }

    public function testFeatureToggleDisabledEvent(): void
    {
        $instance = new FeatureToggleDisabledEvent(
            new DefaultFeature('test', false, []),
            new UnleashContext(),
        );
        $instance->getFeature();
        $instance->getContext();
    }

    public function testFeatureToggleMissingStrategyHandlerEvent(): void
    {
        $instance = new FeatureToggleMissingStrategyHandlerEvent(
            new UnleashContext(),
            new DefaultFeature('test', false, []),
        );
        $instance->getContext();
        $instance->getFeature();
    }

    public function testFeatureToggleNotFoundEvent(): void
    {
        $instance = new FeatureToggleNotFoundEvent(
            new UnleashContext(),
            'test',
        );

        $instance->getContext();
        $instance->getFeatureName();
    }

    public function testCompoundException(): void
    {
        $instance = new CompoundException();

        $instance->getExceptions();
    }
}
