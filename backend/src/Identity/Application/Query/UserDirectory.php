<?php

declare(strict_types=1);

namespace App\Identity\Application\Query;

/** Users for other contexts: names next to members, recipients of emails, who may be assigned. */
interface UserDirectory
{
    public function view(int $id): ?UserView;

    /**
     * @param list<int> $ids
     *
     * @return array<int, UserView> by id; unknown ids are left out
     */
    public function views(array $ids): array;

    /**
     * @return list<UserView> active admins
     */
    public function admins(): array;
}
