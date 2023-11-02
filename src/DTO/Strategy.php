<?php

namespace Unleash\Client\DTO;

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

    /**
     * @return array<Variant>
     */
    public function getVariants(): array;

    /**
     * @return array<Segment>
     */
    public function getSegments(): array;

    public function hasNonexistentSegments(): bool;
}
