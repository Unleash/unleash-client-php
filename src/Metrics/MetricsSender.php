<?php

namespace Rikudou\Unleash\Metrics;

interface MetricsSender
{
    public function sendMetrics(MetricsBucket $bucket): void;
}
