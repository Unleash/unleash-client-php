<?php

namespace Unleash\Client\DTO;

interface Segment
{
    public function getId(): int;

    /**
     * @return array<Constraint>
     */
    public function getConstraints(): array;
}
