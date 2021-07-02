<?php

namespace Strategy;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Pure;
use PHPUnit\Framework\TestCase;
use Rikudou\Unleash\Configuration\UnleashContext;
use Rikudou\Unleash\DTO\DefaultStrategy;
use Rikudou\Unleash\DTO\Strategy;
use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Exception\MissingArgumentException;
use Rikudou\Unleash\Stickiness\MurmurHashCalculator;
use Rikudou\Unleash\Strategy\GradualRolloutStrategyHandler;

final class GradualRolloutStrategyHandlerTest extends TestCase
{
    private GradualRolloutStrategyHandler $instance;

    protected function setUp(): void
    {
        $this->instance = new GradualRolloutStrategyHandler(new MurmurHashCalculator());
    }

    public function testSupports()
    {
        self::assertFalse($this->instance->supports(new DefaultStrategy('default', [])));
        self::assertTrue($this->instance->supports(new DefaultStrategy('flexibleRollout', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('remoteAddress', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('userWithId', [])));
        self::assertFalse($this->instance->supports(new DefaultStrategy('nonexistent', [])));
    }

    public function testIsEnabled()
    {
        // no exception should be thrown
        $this->instance->isEnabled($this->createStrategy(), new UnleashContext());

        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext(currentUserId: '123')));
        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext(currentUserId: '456')));
        self::assertTrue($this->instance->isEnabled($this->createStrategy(50), new UnleashContext(currentUserId: '852')));

        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext(sessionId: '123')));
        self::assertFalse($this->instance->isEnabled($this->createStrategy(50), new UnleashContext(sessionId: '456')));
        self::assertTrue($this->instance->isEnabled($this->createStrategy(50), new UnleashContext(sessionId: '852')));

        self::assertFalse(
            $this->instance->isEnabled(
            $this->createStrategy(50),
            new UnleashContext(currentUserId: '123', sessionId: '852')
        )
        );
        self::assertFalse(
            $this->instance->isEnabled(
            $this->createStrategy(50),
            new UnleashContext(currentUserId: '456', sessionId: '852')
        )
        );
        self::assertTrue(
            $this->instance->isEnabled(
            $this->createStrategy(50),
            new UnleashContext(currentUserId: '852', sessionId: '123')
        )
        );

        try {
            $this->instance->isEnabled(
                $this->createStrategy(stickiness: Stickiness::USER_ID),
                new UnleashContext(),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        try {
            $this->instance->isEnabled(
                $this->createStrategy(stickiness: Stickiness::USER_ID),
                new UnleashContext(sessionId: 'test'),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        self::assertFalse(
            $this->instance->isEnabled(
            $this->createStrategy(percentage: 50, stickiness: Stickiness::USER_ID),
            new UnleashContext(currentUserId: '456')
        )
        );
        self::assertTrue(
            $this->instance->isEnabled(
            $this->createStrategy(percentage: 50, stickiness: Stickiness::USER_ID),
            new UnleashContext(currentUserId: '852')
        )
        );

        try {
            $this->instance->isEnabled(
                $this->createStrategy(stickiness: Stickiness::SESSION_ID),
                new UnleashContext(),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        try {
            $this->instance->isEnabled(
                $this->createStrategy(stickiness: Stickiness::SESSION_ID),
                new UnleashContext(currentUserId: 'test'),
            );
            $this->fail('Expected exception of class ' . MissingArgumentException::class);
        } catch (MissingArgumentException $e) {
        }

        self::assertFalse(
            $this->instance->isEnabled(
            $this->createStrategy(percentage: 50, stickiness: Stickiness::SESSION_ID),
            new UnleashContext(sessionId: '456')
        )
        );
        self::assertTrue(
            $this->instance->isEnabled(
            $this->createStrategy(percentage: 50, stickiness: Stickiness::SESSION_ID),
            new UnleashContext(sessionId: '852')
        )
        );

        $this->instance->isEnabled($this->createStrategy(stickiness: Stickiness::RANDOM), new UnleashContext());
    }

    #[Pure]
    private function createStrategy(
        int $percentage = 100,
        #[ExpectedValues(valuesFromClass: Stickiness::class)]
        $stickiness = Stickiness::DEFAULT
    ): Strategy {
        return new DefaultStrategy('flexibleRollout', [
            'stickiness' => $stickiness,
            'groupId'=> 'default',
            'rollout' => $percentage,
        ]);
    }
}
