<?php

namespace App\Repository;

use App\Entity\Visit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Visit>
 */
class VisitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Visit::class);
    }

    /* -----------------------------------------------------------
       countTodayGlobal — Total de visites d'avui (tots els clients)
       ----------------------------------------------------------- */
    public function countTodayGlobal(): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.visitedAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* -----------------------------------------------------------
       countTodayByUser — Total de visites d'avui per usuari
       ----------------------------------------------------------- */
    public function countTodayByUser(int $userId): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->where('v.visitedAt >= :today')
            ->andWhere('IDENTITY(v.user) = :userId')
            ->setParameter('today', $today)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
