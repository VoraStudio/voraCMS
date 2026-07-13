<?php

namespace App\Repository;

use App\Entity\ApiRequestLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ApiRequestLog>
 */
class ApiRequestLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiRequestLog::class);
    }

    /* ── Total crides API avui ── */
    public function countToday(): int
    {
        $today = new \DateTimeImmutable('today');
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :today')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* ── Crides per rang de status avui ── */
    public function countByStatusRangeToday(int $min, int $max): int
    {
        $today = new \DateTimeImmutable('today');
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :today')
            ->andWhere('r.statusCode >= :min')
            ->andWhere('r.statusCode <= :max')
            ->setParameter('today', $today)
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* ── Token grants avui ── */
    public function countTokenGrantsToday(): int
    {
        $today = new \DateTimeImmutable('today');
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :today')
            ->andWhere('r.endpoint = :ep')
            ->andWhere('r.granted = :val')
            ->setParameter('today', $today)
            ->setParameter('ep', '/api/public/token')
            ->setParameter('val', true)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* ── Token denials avui ── */
    public function countTokenDenialsToday(): int
    {
        $today = new \DateTimeImmutable('today');
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.createdAt >= :today')
            ->andWhere('r.endpoint = :ep')
            ->andWhere('r.granted = :val')
            ->setParameter('today', $today)
            ->setParameter('ep', '/api/public/token')
            ->setParameter('val', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* ── Temps de resposta mig avui (ms) ── */
    public function averageResponseTimeToday(): int
    {
        $today = new \DateTimeImmutable('today');
        $result = (int) $this->createQueryBuilder('r')
            ->select('AVG(r.responseTimeMs)')
            ->where('r.createdAt >= :today')
            ->andWhere('r.responseTimeMs IS NOT NULL')
            ->setParameter('today', $today)
            ->getQuery()
            ->getSingleScalarResult();
        return $result;
    }

    /* ── Top origins consumint l'API avui ── */
    public function topOriginsToday(int $limit = 5): array
    {
        $today = new \DateTimeImmutable('today');
        return $this->createQueryBuilder('r')
            ->select('r.origin, COUNT(r.id) AS total')
            ->where('r.createdAt >= :today')
            ->andWhere('r.origin IS NOT NULL')
            ->andWhere("r.origin != ''")
            ->groupBy('r.origin')
            ->orderBy('total', 'DESC')
            ->setParameter('today', $today)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /* ── Crides per endpoint avui (top) ── */
    public function topEndpointsToday(int $limit = 5): array
    {
        $today = new \DateTimeImmutable('today');
        return $this->createQueryBuilder('r')
            ->select('r.endpoint, COUNT(r.id) AS total')
            ->where('r.createdAt >= :today')
            ->groupBy('r.endpoint')
            ->orderBy('total', 'DESC')
            ->setParameter('today', $today)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
