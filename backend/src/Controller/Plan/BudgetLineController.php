<?php

namespace App\Controller\Plan;

use App\Dto\BudgetLineInput;
use App\Entity\BudgetLine;
use App\Entity\Category;
use App\Exception\ApiProblem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/budget-lines/{id}', requirements: ['id' => '\d+'])]
class BudgetLineController extends AbstractPlanController
{
    #[Route('', methods: ['PUT'])]
    public function update(BudgetLine $line, #[MapRequestPayload] BudgetLineInput $input): JsonResponse
    {
        $project = $line->getStage()->getProject();
        $this->denyUnlessEditable($project);

        $category = $this->em->find(Category::class, $input->categoryId);
        if (null === $category || $category->getProject() !== $project) {
            throw ApiProblem::field('categoryId', 'Categoría inválida.');
        }
        $line->update($category, $input->description, $input->unit, $input->quantity, $this->toMinor($input->unitPrice, $project, 'unitPrice'));

        return $this->flushAndPlan($project);
    }

    #[Route('', methods: ['DELETE'])]
    public function delete(BudgetLine $line): JsonResponse
    {
        $project = $line->getStage()->getProject();
        $this->denyUnlessEditable($project);
        $line->getStage()->removeBudgetLine($line);

        return $this->flushAndPlan($project);
    }
}
