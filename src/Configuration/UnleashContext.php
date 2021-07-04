<?php

namespace Rikudou\Unleash\Configuration;

use Rikudou\Unleash\Enum\Stickiness;
use Rikudou\Unleash\Exception\InvalidValueException;

final class UnleashContext
{
    /**
     * @var array<string,string>
     */
    private array $customContext = [];

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

    public function getIpAddress(): ?string
    {
        return $this->ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId ?? (session_id() ?: null);
    }

    public function getCustomProperty(string $name): string
    {
        if (!array_key_exists($name, $this->customContext)) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        return $this->customContext[$name];
    }

    public function setCustomProperty(string $name, string $value): self
    {
        $this->customContext[$name] = $value;

        return $this;
    }

    public function hasCustomProperty(string $name): bool
    {
        return array_key_exists($name, $this->customContext);
    }

    public function removeCustomProperty(string $name, bool $silent = true): self
    {
        if (!$this->hasCustomProperty($name) && !$silent) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        unset($this->customContext[$name]);

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setCurrentUserId(?string $currentUserId): UnleashContext
    {
        $this->currentUserId = $currentUserId;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setIpAddress(?string $ipAddress): UnleashContext
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    /**
     * @codeCoverageIgnore
     */
    public function setSessionId(?string $sessionId): UnleashContext
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @param array<string> $values
     */
    public function hasMatchingFieldValue(string $fieldName, array $values): bool
    {
        $fieldValue = $this->findContextValue($fieldName);
        if ($fieldValue === null) {
            return false;
        }

        return in_array($fieldValue, $values, true);
    }

    public function findContextValue(string $fieldName): ?string
    {
        return match ($fieldName) {
            Stickiness::USER_ID => $this->getCurrentUserId(),
            Stickiness::SESSION_ID => $this->getSessionId(),
            Stickiness::IP_ADDRESS => $this->getIpAddress(),
            default => $this->customContext[$fieldName] ?? null,
        };
    }
}
