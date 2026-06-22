<?php

namespace App\Repository;

use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProject::class);
    }

    public function findOneByUserAndProject(User $user, Project $project): ?UserProject
    {
        return $this->createQueryBuilder('up')
            ->where('up.user = :user')
            ->andWhere('up.project = :project')
            ->setParameter('user', $user)
            ->setParameter('project', $project)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return UserProject[]
     */
    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('up')
            ->where('up.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return UserProject[]
     */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('up')
            ->where('up.project = :project')
            ->setParameter('project', $project)
            ->getQuery()
            ->getResult();
    }
}
