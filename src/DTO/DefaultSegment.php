<?php

namespace Unleash\Client\DTO;

final class DefaultSegment implements Segment
{
    /**
     * @param array<Constraint> $constraints
     */
    public function __construct(
        private readonly int $id,
        private readonly array $constraints,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getConstraints(): array
    {
        return $this->constraints;
    }
}
