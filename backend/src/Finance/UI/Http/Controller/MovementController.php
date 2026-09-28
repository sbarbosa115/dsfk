<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Controller;

use App\Document\Application\Command\AttachFile;
use App\Document\Application\Command\Upload;
use App\Finance\Application\Command\VoidMovement;
use App\Finance\Application\Query\FinanceQueries;
use App\Finance\Domain\Repository\LedgerRepository;
use App\Finance\UI\Http\Input\VoidInput;
use App\Finance\UI\Http\Output\MovementOutput;
use App\Finance\UI\Http\Presenter\FinancePresenter;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Security\Actor;
use App\Shared\Domain\Error\InvalidValue;
use App\Shared\Domain\Error\NotFound;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[Route('/api/movements/{id}', requirements: ['id' => '\d+'])]
#[OA\Tag(name: 'Finance')]
#[OA\Response(response: 403, description: 'forbidden: Admins only', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, description: 'movement_not_found, or project_not_found outside the project', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class MovementController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly ProjectGuard $guard,
        private readonly FinancePresenter $presenter,
        private readonly FinanceQueries $queries,
        private readonly LedgerRepository $ledger,
    ) {
    }

    /** Deposits and draws only, with a reason; the record stays, out of the balances. */
    #[Route('/void', name: 'api_movements_void', methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The voided movement', content: new Model(type: MovementOutput::class))]
    #[OA\Response(response: 409, description: 'movement_not_voidable, movement_already_voided, void_would_overdraw, cycle_closed', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function void(int $id, #[MapRequestPayload] VoidInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::ADMINISTER, $this->project($id));
        $this->bus->dispatch(new VoidMovement($id, $actor->getId(), $input->reason));

        return $this->json($this->presenter->movement($this->ledger->movement($id)));
    }

    /** A proof (multipart field "file"): PDF, JPG, PNG, WEBP or HEIC up to 10 MB, checked by content. */
    #[Route('/attachments', name: 'api_movements_attach', methods: ['POST'])]
    #[OA\RequestBody(content: new OA\MediaType(mediaType: 'multipart/form-data', schema: new OA\Schema(required: ['file'], properties: [new OA\Property(property: 'file', type: 'string', format: 'binary')])))]
    #[OA\Response(response: 201, description: 'The movement with its files', content: new Model(type: MovementOutput::class))]
    #[OA\Response(response: 422, description: 'validation_failed on "file"', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function attach(int $id, Request $request, #[CurrentUser] Actor $actor): JsonResponse
    {
        $project = $this->project($id);
        $this->guard->require(ProjectPermission::ADMINISTER, $project);
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            throw InvalidValue::field('file', 'Select a file.');
        }
        if (!$file->isValid()) {
            throw InvalidValue::field('file', \UPLOAD_ERR_INI_SIZE === $file->getError() || \UPLOAD_ERR_FORM_SIZE === $file->getError() ? 'The file is larger than 10 MB.' : 'The file could not be uploaded.');
        }
        $this->bus->dispatch(AttachFile::toMovement($project, $id, new Upload($file->getPathname(), $file->getClientOriginalName(), (int) $file->getSize()), $actor->getId()));

        return $this->json($this->presenter->movement($this->ledger->movement($id)), 201);
    }

    private function project(int $movementId): int
    {
        return $this->queries->projectOfMovement($movementId) ?? throw new NotFound('movement_not_found');
    }
}
