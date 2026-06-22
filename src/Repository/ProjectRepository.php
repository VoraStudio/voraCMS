<?php

namespace App\Repository;

use App\Entity\Project;
use App\Service\ClientScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClientScope $clientScope,
    ) {
        parent::__construct($registry, Project::class);
    }

    public function findActive(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC');

        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(p.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        return $qb->getQuery()->getResult();
    }

    /* -----------------------------------------------------------
       findLatestActive — Últims projectes actius (sense scope de client,
       per al dashboard d'admin). Inclou el client per mostrar-lo.
       ----------------------------------------------------------- */
    public function findLatestActive(int $limit = 6): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.client', 'c')
            ->addSelect('c')
            ->where('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findBySlug(string $slug): ?Project
    {
        $qb = $this->createQueryBuilder('p')
            ->where('p.slug = :slug')
            ->setParameter('slug', $slug);

        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(p.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
