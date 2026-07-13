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

    /* ── Visites per dia (últims 7 dies) ── */
    public function countByDayLast7(): array
    {
        $sevenDaysAgo = new \DateTimeImmutable('-7 days midnight');

        $rows = $this->createQueryBuilder('v')
            ->select("DATE(v.visitedAt) AS dia, COUNT(v.id) AS total")
            ->where('v.visitedAt >= :since')
            ->groupBy('dia')
            ->orderBy('dia', 'ASC')
            ->setParameter('since', $sevenDaysAgo)
            ->getQuery()
            ->getResult();

        /* Omplir amb 0 els dies sense visites */
        $visitsByDay = [];
        foreach ($rows as $r) {
            $visitsByDay[$r['dia']] = (int) $r['total'];
        }

        $result = [];
        $now = new \DateTimeImmutable('today');
        for ($i = 6; $i >= 0; $i--) {
            $day = $now->modify("-{$i} days")->format('Y-m-d');
            $result[] = [
                'date' => $day,
                'total' => $visitsByDay[$day] ?? 0,
            ];
        }

        return $result;
    }
}
