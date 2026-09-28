<?php

declare(strict_types=1);

namespace App\Document\Domain\Repository;

use App\Document\Domain\Model\Attachment;

interface AttachmentRepository
{
    public function add(Attachment $attachment): void;
}
