<?php

namespace App\Controller\Plan;

use App\Dto\CategoryInput;
use App\Entity\BudgetLine;
use App\Entity\Category;
use App\Exception\ApiProblem;
use App\Security\ProjectVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/categories/{id}', requirements: ['id' => '\d+'])]
class CategoryController extends AbstractPlanController
{
    #[Route('', methods: ['PATCH'])]
    public function rename(Category $category, #[MapRequestPayload] CategoryInput $input): JsonResponse
    {
        $project = $category->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::PLAN, $project);
        foreach ($project->getCategories() as $other) {
            if ($other !== $category && mb_strtolower($other->getName()) === mb_strtolower(trim($input->name))) {
                throw ApiProblem::field('name', 'Ya existe una categoría con ese nombre.');
            }
        }
        $category->setName($input->name);

        return $this->flushAndPlan($project);
    }

    #[Route('', methods: ['DELETE'])]
    public function delete(Category $category): JsonResponse
    {
        $project = $category->getProject();
        $this->denyAccessUnlessGranted(ProjectVoter::PLAN, $project);
        if (null !== $this->em->getRepository(BudgetLine::class)->findOneBy(['category' => $category])) {
            throw new \DomainException('category_in_use');
        }
        $project->getCategories()->removeElement($category);
        $this->em->remove($category);

        return $this->flushAndPlan($project);
    }
}
