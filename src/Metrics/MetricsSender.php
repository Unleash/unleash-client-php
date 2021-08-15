<?php

namespace Unleash\Client\Metrics;

interface MetricsSender
{
    public function sendMetrics(MetricsBucket $bucket): void;
}
