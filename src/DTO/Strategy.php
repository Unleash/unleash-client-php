<?php

namespace Unleash\Client\DTO;

/**
 * @method array<Segment> getSegments()
 * @method bool           hasNonexistentSegments()
 */
interface Strategy
{
    public function getName(): string;

    /**
     * @return array<string, string>
     */
    public function getParameters(): array;

    /**
     * @return array<Constraint>
     */
    public function getConstraints(): array;
}
