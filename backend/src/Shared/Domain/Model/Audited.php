<?php

declare(strict_types=1);

namespace App\Shared\Domain\Model;

/**
 * An entity whose changes go to the audit trail (who changed what, when). The Audit context records every insert,
 * update and delete of these, with the project they belong to.
 */
interface Audited
{
    /** The project the record belongs to; null for global records (users, settings). */
    public function auditProjectId(): ?int;
}
