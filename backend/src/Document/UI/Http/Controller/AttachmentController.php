<?php

declare(strict_types=1);

namespace App\Document\UI\Http\Controller;

use App\Document\Application\Port\FileStore;
use App\Document\Application\Query\AttachmentQueries;
use App\Expense\Application\Query\ExpenseDirectory;
use App\Shared\Application\Security\Actor;
use App\Shared\Domain\Error\NotFound;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Documents')]
final class AttachmentController extends AbstractController
{
    public function __construct(
        private readonly AttachmentQueries $attachments,
        private readonly FileStore $files,
        private readonly ProjectGuard $guard,
        private readonly ExpenseDirectory $expenses,
    ) {
    }

    /** Opens a file in the browser: for the project's Admins and Project Manager, and a Team Lead's own receipts. */
    #[Route('/api/attachments/{id}', name: 'api_attachments_download', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The file, inline, with its stored type', content: new OA\MediaType(mediaType: 'application/octet-stream'))]
    #[OA\Response(response: 403, description: 'forbidden (Team Leads)', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 404, description: 'attachment_not_found, file_missing, or project_not_found outside the project', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function download(int $id, #[CurrentUser] Actor $actor): BinaryFileResponse
    {
        $attachment = $this->attachments->find($id) ?? throw new NotFound('attachment_not_found');
        $ownReceipt = null !== $attachment->expenseId && $this->expenses->paidBy($attachment->expenseId) === $actor->getId();
        $this->guard->require($ownReceipt ? ProjectPermission::VIEW : ProjectPermission::VIEW_FINANCIALS, $attachment->projectId);
        $path = $this->files->locate($attachment->storedName) ?? throw new NotFound('file_missing');

        $response = new BinaryFileResponse($path);
        $response->headers->set('Content-Type', $attachment->mimeType);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Content-Security-Policy', "default-src 'none'; sandbox");
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $attachment->name, 'archivo');

        return $response;
    }
}
