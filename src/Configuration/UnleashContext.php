<?php

namespace Rikudou\Unleash\Configuration;

final class UnleashContext
{
    public function __construct(
        private ?string $currentUserId = null,
        private ?string $ipAddress = null,
        private ?string $sessionId = null,
    ) {
    }

    public function getCurrentUserId(): ?string
    {
        return $this->currentUserId;
    }

    public function getIpAddress(): string
    {
        return $this->ipAddress ?? $_SERVER['REMOTE_ADDR'];
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId ?? (session_id() ?: null);
    }
}
