<?php

namespace Unleash\Client\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use Unleash\Client\Enum\Stickiness;

interface ProxyVariant extends JsonSerializable
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getPayload(): ?VariantPayload;
}