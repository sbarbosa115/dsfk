<?php

declare(strict_types=1);

namespace App\Notification\Application\Port;

/** Who gets an email: active accounts only (from Identity and Project). */
interface Recipients
{
    /**
     * @return list<Recipient>
     */
    public function admins(): array;

    public function projectManager(int $projectId): ?Recipient;

    public function person(int $userId): ?Recipient;
}
