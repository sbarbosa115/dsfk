<?php

declare(strict_types=1);

namespace App\Planning\UI\Http\Controller;

use App\Planning\Application\Command\DeleteCategory;
use App\Planning\Application\Command\RenameCategory;
use App\Planning\UI\Http\Input\CategoryInput;
use App\Planning\UI\Http\Output\PlanOutput;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories/{id}', requirements: ['id' => '\d+'])]
#[OA\Tag(name: 'Plan')]
#[OA\Response(response: 200, description: 'The refreshed plan', content: new Model(type: PlanOutput::class))]
final class CategoryController extends PlanEndpoint
{
    #[Route('', name: 'api_categories_rename', methods: ['PATCH'])]
    public function rename(int $id, #[MapRequestPayload] CategoryInput $input): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new RenameCategory($id, $input->name));

        return $this->plan($project);
    }

    /** Only a category nothing uses. */
    #[Route('', name: 'api_categories_delete', methods: ['DELETE'])]
    #[OA\Response(response: 409, description: 'category_in_use', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function delete(int $id): JsonResponse
    {
        $project = $this->project($id);
        $this->bus->dispatch(new DeleteCategory($id));

        return $this->plan($project);
    }

    private function project(int $categoryId): int
    {
        $project = $this->known($this->parts->projectOfCategory($categoryId), 'category_not_found');
        $this->guard(ProjectPermission::PLAN, $project);

        return $project;
    }
}
