<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Project>
 */
class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    /**
     * Admins see every project; everyone else only sees projects they belong to.
     *
     * @return list<Project>
     */
    public function findVisibleTo(User $user): array
    {
        $qb = $this->createQueryBuilder('p')->orderBy('p.createdAt', 'DESC');

        if (!$user->isAdmin()) {
            $qb->innerJoin('p.members', 'm')
                ->andWhere('m.user = :user')
                ->setParameter('user', $user);
        }

        return $qb->getQuery()->getResult();
    }
}
