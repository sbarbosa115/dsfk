<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/**
 * A rule said no. The code is a stable snake_case string the UI translates; the extra data (e.g. the available
 * amount) is sent along with it. Each subclass is a kind that maps to one HTTP status.
 */
abstract class DomainError extends \DomainException
{
    /**
     * @param array<string, mixed> $extra
     */
    final public function __construct(public readonly string $errorCode, public readonly array $extra = [])
    {
        parent::__construct($errorCode);
    }

    abstract public function httpStatus(): int;

    /** A 422 on one field, in the same shape as the validator's violations. */
    public static function field(string $field, string $message): static
    {
        return new static('validation_failed', ['violations' => [$field => [$message]]]);
    }
}
