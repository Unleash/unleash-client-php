<?php

namespace Rikudou\Unleash\DTO;

use JetBrains\PhpStorm\ExpectedValues;
use JsonSerializable;
use LogicException;
use Rikudou\Unleash\Enum\VariantPayloadType;

interface VariantPayload extends JsonSerializable
{
    #[ExpectedValues(valuesFromClass: VariantPayloadType::class)]
    public function getType(): string;

    public function getValue(): string;

    /**
     * @throws LogicException
     *
     * @return array<mixed>
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function fromJson(): array;
}
