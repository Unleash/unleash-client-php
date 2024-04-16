<?php

namespace Unleash\Client\DTO;

final class DefaultSegment implements Segment
{
    /**
     * @param array<Constraint> $constraints
     */
    public function __construct(
        private int $id,
        private array $constraints,
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
