<?php

declare(strict_types=1);

namespace App\Shared\Application\Bus;

/**
 * The id of a record a handler created. Ids are auto-increment and handlers never flush, so the id exists only
 * once the bus has committed: read it after dispatch() returns.
 */
final readonly class NewId
{
    /**
     * @param \Closure(): ?int $resolve
     */
    private function __construct(private \Closure $resolve)
    {
    }

    /**
     * @param object $record an entity with a getId(): ?int method
     */
    public static function of(object $record): self
    {
        return new self(static function () use ($record): ?int {
            \assert(method_exists($record, 'getId'));

            return $record->getId();
        });
    }

    public function value(): int
    {
        $id = ($this->resolve)();
        if (null === $id) {
            throw new \LogicException('The id is only known after the command has been committed.');
        }

        return $id;
    }
}
