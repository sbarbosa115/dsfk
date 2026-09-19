<?php

namespace App\Controller\Plan;

use App\Entity\Project;
use App\Exception\ApiProblem;
use App\Security\ProjectVoter;
use App\Service\MoneyConverter;
use App\Service\PlanPresenter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Every plan mutation answers with the whole refreshed plan, so the UI never
 * has to recompute totals or progress itself.
 */
abstract class AbstractPlanController extends AbstractController
{
    protected EntityManagerInterface $em;
    private PlanPresenter $presenter;

    #[Required]
    public function setPlanDependencies(EntityManagerInterface $em, PlanPresenter $presenter): void
    {
        $this->em = $em;
        $this->presenter = $presenter;
    }

    protected function plan(Project $project, int $status = 200): JsonResponse
    {
        return $this->json($this->presenter->present($project), $status);
    }

    protected function flushAndPlan(Project $project): JsonResponse
    {
        $this->em->flush();
        // Collections were changed in memory; reload everything so ordering and totals are exact.
        $id = $project->getId();
        $this->em->clear();

        return $this->plan($this->em->find(Project::class, $id));
    }

    /** PM or Admin, while the budget is still a draft (or was returned). */
    protected function denyUnlessEditable(Project $project): void
    {
        $this->denyAccessUnlessGranted(ProjectVoter::PLAN, $project);
        $project->getBudget()->assertEditable();
    }

    /** PM or Admin, once the budget is approved (execution tracking). */
    protected function denyUnlessTracking(Project $project): void
    {
        $this->denyAccessUnlessGranted(ProjectVoter::PLAN, $project);
        if (!$project->getBudget()->isApproved()) {
            throw new \DomainException('budget_not_approved');
        }
    }

    protected function toMinor(string $amount, Project $project, string $field): int
    {
        try {
            return MoneyConverter::toMinor($amount, $project->getCurrency());
        } catch (\InvalidArgumentException) {
            throw ApiProblem::field($field, 'Monto inválido para la moneda del proyecto.');
        }
    }
}
