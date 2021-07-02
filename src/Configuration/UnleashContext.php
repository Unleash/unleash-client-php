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

    public function setCurrentUserId(?string $currentUserId): UnleashContext
    {
        $this->currentUserId = $currentUserId;

        return $this;
    }

    public function setIpAddress(?string $ipAddress): UnleashContext
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    public function setSessionId(?string $sessionId): UnleashContext
    {
        $this->sessionId = $sessionId;

        return $this;
    }
}
