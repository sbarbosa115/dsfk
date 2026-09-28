<?php

declare(strict_types=1);

namespace App\Shared\Domain\Error;

/**
 * A rule said no. The code is a stable snake_case string the UI translates; the extra data (e.g. the available
 * amount) is sent along with it. Each subclass is a kind that maps to one HTTP status. Field messages are written
 * in English and translated when the API renders them (translations/validators.es.yaml).
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

    /**
     * A 422 on one field, in the same shape as the validator's violations.
     *
     * @param array<string, string> $parameters placeholders of the message, e.g. ['%available%' => '$ 300.000']
     */
    public static function field(string $field, string $message, array $parameters = []): static
    {
        return new static('validation_failed', self::violation($field, $message, $parameters));
    }

    /**
     * The extra data of a message on one field: the message, and its placeholders for the translation.
     *
     * @param array<string, string> $parameters
     *
     * @return array<string, mixed>
     */
    public static function violation(string $field, string $message, array $parameters = []): array
    {
        return ['violations' => [$field => [$message]]] + ([] === $parameters ? [] : ['violationParameters' => [$message => $parameters]]);
    }
}
