<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * An API error with a stable code and optional extra payload, rendered as
 * {"error": code, ...extra}.
 */
class ApiProblem extends HttpException
{
    /**
     * @param array<string, mixed> $extra
     */
    public function __construct(string $code, private readonly array $extra = [], int $status = 422)
    {
        parent::__construct($status, $code);
    }

    /** Single-field validation error, same shape as validator failures. */
    public static function field(string $field, string $message): self
    {
        return new self('validation_failed', ['violations' => [$field => [$message]]]);
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }
}
