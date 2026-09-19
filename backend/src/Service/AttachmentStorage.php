<?php

namespace App\Service;

use App\Entity\Attachment;
use App\Entity\Project;
use App\Entity\User;
use App\Exception\ApiProblem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Stores uploads under var/uploads (outside public/), with random names.
 */
class AttachmentStorage
{
    public const MAX_SIZE = 10 * 1024 * 1024;
    public const ALLOWED_TYPES = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/heic' => 'heic',
    ];

    public function __construct(#[Autowire('%app.upload_dir%')] private readonly string $uploadDir)
    {
    }

    public function store(UploadedFile $file, Project $project, User $by): Attachment
    {
        if (!$file->isValid()) {
            throw ApiProblem::field('file', 'No se pudo subir el archivo.');
        }
        if ($file->getSize() > self::MAX_SIZE) {
            throw ApiProblem::field('file', 'El archivo supera el máximo de 10 MB.');
        }
        // Content sniffing, not the client-provided type.
        $mime = $file->getMimeType() ?? '';
        $extension = self::ALLOWED_TYPES[$mime] ?? null;
        if (null === $extension) {
            throw ApiProblem::field('file', 'Formato no permitido. Usa PDF, JPG, PNG, WEBP o HEIC.');
        }

        $storedName = \sprintf('%d/%s.%s', $project->getId(), bin2hex(random_bytes(16)), $extension);
        $size = (int) $file->getSize();
        $file->move(\dirname($this->path($storedName)), basename($storedName));

        return new Attachment($project, $storedName, $file->getClientOriginalName(), $mime, $size, $by);
    }

    public function path(Attachment|string $attachment): string
    {
        $name = $attachment instanceof Attachment ? $attachment->getStoredName() : $attachment;

        return $this->uploadDir.'/'.$name;
    }
}
