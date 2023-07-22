<?php

namespace Unleash\Client\Metrics;

use DateTimeInterface;
use JsonSerializable;

interface MetricsBucket extends JsonSerializable
{
    public function getStartDate(): DateTimeInterface;

    public function setStartDate(DateTimeInterface $date): self;

    public function getEndDate(): ?DateTimeInterface;

    public function setEndDate(?DateTimeInterface $date): self;

    public function addToggle(MetricsBucketToggle $toggle): self;
}
