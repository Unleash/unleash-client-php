<?php

namespace Unleash\Client\Configuration;

use DateTimeImmutable;
use DateTimeInterface;
use Override;
use Unleash\Client\Enum\ContextField;
use Unleash\Client\Enum\Stickiness;
use Unleash\Client\Exception\InvalidValueException;

final class UnleashContext implements Context
{
    /**
     * @param array<string,string> $customContext
     */
    public function __construct(
        private ?string $currentUserId = null,
        private ?string $ipAddress = null,
        private ?string $sessionId = null,
        private array $customContext = [],
        ?string $hostname = null,
        private ?string $environment = null,
        DateTimeInterface|string|null $currentTime = null,
    ) {
        $this->setHostname($hostname);
        $this->setCurrentTime($currentTime);
    }

    #[Override]
    public function getCurrentUserId(): ?string
    {
        return $this->currentUserId;
    }

    #[Override]
    public function getEnvironment(): ?string
    {
        return $this->environment;
    }

    #[Override]
    public function getIpAddress(): ?string
    {
        assert(is_string($_SERVER['REMOTE_ADDR']) || $_SERVER['REMOTE_ADDR'] === null);

        return $this->ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null;
    }

    #[Override]
    public function getSessionId(): ?string
    {
        return $this->sessionId ?? (session_id() ?: null);
    }

    #[Override]
    public function getCustomProperty(string $name): string
    {
        if (!array_key_exists($name, $this->customContext)) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        return $this->customContext[$name];
    }

    #[Override]
    public function setCustomProperty(string $name, ?string $value): self
    {
        $this->customContext[$name] = $value ?? '';

        return $this;
    }

    #[Override]
    public function hasCustomProperty(string $name): bool
    {
        return array_key_exists($name, $this->customContext);
    }

    #[Override]
    public function removeCustomProperty(string $name, bool $silent = true): self
    {
        if (!$this->hasCustomProperty($name) && !$silent) {
            throw new InvalidValueException("The custom context value '{$name}' does not exist");
        }

        unset($this->customContext[$name]);

        return $this;
    }

    #[Override]
    public function setCurrentUserId(?string $currentUserId): self
    {
        $this->currentUserId = $currentUserId;

        return $this;
    }

    #[Override]
    public function setIpAddress(?string $ipAddress): self
    {
        $this->ipAddress = $ipAddress;

        return $this;
    }

    #[Override]
    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    #[Override]
    public function setEnvironment(?string $environment): self
    {
        $this->environment = $environment;

        return $this;
    }

    #[Override]
    public function getHostname(): ?string
    {
        return $this->findContextValue(ContextField::HOSTNAME) ?? (gethostname() ?: null);
    }

    #[Override]
    public function setHostname(?string $hostname): self
    {
        if ($hostname === null) {
            $this->removeCustomProperty(ContextField::HOSTNAME);
        } else {
            $this->setCustomProperty(ContextField::HOSTNAME, $hostname);
        }

        return $this;
    }

    /**
     * @param array<string> $values
     */
    #[Override]
    public function hasMatchingFieldValue(string $fieldName, array $values): bool
    {
        $fieldValue = $this->findContextValue($fieldName);
        if ($fieldValue === null) {
            return false;
        }

        return in_array($fieldValue, $values, true);
    }

    #[Override]
    public function findContextValue(string $fieldName): ?string
    {
        return match ($fieldName) {
            ContextField::USER_ID, Stickiness::USER_ID => $this->getCurrentUserId(),
            ContextField::SESSION_ID, Stickiness::SESSION_ID => $this->getSessionId(),
            ContextField::IP_ADDRESS => $this->getIpAddress(),
            ContextField::ENVIRONMENT => $this->getEnvironment(),
            ContextField::CURRENT_TIME => $this->getCurrentTime()->format(DateTimeInterface::ISO8601),
            default => $this->customContext[$fieldName] ?? null,
        };
    }

    #[Override]
    public function getCurrentTime(): DateTimeInterface
    {
        if (!$this->hasCustomProperty('currentTime')) {
            return new DateTimeImmutable();
        }

        return new DateTimeImmutable($this->getCustomProperty('currentTime'));
    }

    #[Override]
    public function setCurrentTime(DateTimeInterface|string|null $time): self
    {
        if ($time === null) {
            $this->removeCustomProperty('currentTime');
        } else {
            $value = is_string($time) ? $time : $time->format(DateTimeInterface::ISO8601);
            $this->setCustomProperty('currentTime', $value);
        }

        return $this;
    }

    /**
     * @return array<string, string>
     */
    #[Override]
    public function getCustomProperties(): array
    {
        return $this->customContext;
    }
}
