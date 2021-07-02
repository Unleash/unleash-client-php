<?php

namespace Rikudou\Unleash\DTO;

interface Strategy
{
    public function getName(): string;

    /**
     * @return array<string, string>
     */
    public function getParameters(): array;
}
