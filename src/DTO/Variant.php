<?php

namespace Unleash\Client\DTO;

use JsonSerializable;

interface Variant extends JsonSerializable
{
    public function getName(): string;

    public function isEnabled(): bool;

    public function getPayload(): ?VariantPayload;
}
