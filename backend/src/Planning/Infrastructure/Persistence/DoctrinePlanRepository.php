<?php

declare(strict_types=1);

namespace App\Planning\Infrastructure\Persistence;

use App\Planning\Application\Query\PlanQueries;
use App\Planning\Domain\Model\Budget;
use App\Planning\Domain\Model\BudgetLine;
use App\Planning\Domain\Model\Category;
use App\Planning\Domain\Model\Milestone;
use App\Planning\Domain\Model\Stage;
use App\Planning\Domain\Repository\PlanRepository;
use App\Shared\Domain\Error\NotFound;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DoctrinePlanRepository implements PlanRepository, PlanQueries
{
    public function __construct(private EntityManagerInterface $em)
    {
    }

    public function budgetFor(int $projectId): Budget
    {
        $budget = $this->em->getRepository(Budget::class)->findOneBy(['projectId' => $projectId]);
        if (null === $budget) {
            // Also any budget persisted earlier in this transaction, not flushed yet.
            foreach ($this->em->getUnitOfWork()->getScheduledEntityInsertions() as $scheduled) {
                if ($scheduled instanceof Budget && $scheduled->getProjectId() === $projectId) {
                    return $scheduled;
                }
            }
            $budget = new Budget($projectId);
            $this->em->persist($budget);
        }

        return $budget;
    }

    public function findBudget(int $projectId): ?Budget
    {
        return $this->em->getRepository(Budget::class)->findOneBy(['projectId' => $projectId]);
    }

    public function stagesOf(int $projectId): array
    {
        return $this->em->createQueryBuilder()
            ->select('s', 'l', 'm', 'c')
            ->from(Stage::class, 's')
            ->leftJoin('s.lines', 'l')
            ->leftJoin('l.category', 'c')
            ->leftJoin('s.milestones', 'm')
            ->where('s.projectId = :project')
            ->setParameter('project', $projectId)
            ->orderBy('s.position', 'ASC')
            ->addOrderBy('s.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function categoriesOf(int $projectId): array
    {
        return $this->em->getRepository(Category::class)->findBy(['projectId' => $projectId], ['name' => 'ASC']);
    }

    public function stage(int $id): Stage
    {
        return $this->em->find(Stage::class, $id) ?? throw new NotFound('stage_not_found');
    }

    public function category(int $id): Category
    {
        return $this->em->find(Category::class, $id) ?? throw new NotFound('category_not_found');
    }

    public function line(int $id): BudgetLine
    {
        return $this->em->find(BudgetLine::class, $id) ?? throw new NotFound('line_not_found');
    }

    public function milestone(int $id): Milestone
    {
        return $this->em->find(Milestone::class, $id) ?? throw new NotFound('milestone_not_found');
    }

    public function addStage(Stage $stage): void
    {
        $this->em->persist($stage);
    }

    public function removeStage(Stage $stage): void
    {
        $this->em->remove($stage);
    }

    public function addCategory(Category $category): void
    {
        $this->em->persist($category);
    }

    public function removeCategory(Category $category): void
    {
        $this->em->remove($category);
    }

    public function categoryHasLines(Category $category): bool
    {
        return null !== $this->em->getRepository(BudgetLine::class)->findOneBy(['category' => $category]);
    }

    public function projectOfStage(int $stageId): ?int
    {
        return $this->em->find(Stage::class, $stageId)?->getProjectId();
    }

    public function projectOfLine(int $lineId): ?int
    {
        return $this->em->find(BudgetLine::class, $lineId)?->getStage()->getProjectId();
    }

    public function projectOfMilestone(int $milestoneId): ?int
    {
        return $this->em->find(Milestone::class, $milestoneId)?->getStage()->getProjectId();
    }

    public function projectOfCategory(int $categoryId): ?int
    {
        return $this->em->find(Category::class, $categoryId)?->getProjectId();
    }
}
