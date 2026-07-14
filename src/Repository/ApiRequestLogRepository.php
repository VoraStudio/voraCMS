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

    /* ════════════════════════════════════════════════════════════
       MÈTRIQUES PER PROJECTE
       ════════════════════════════════════════════════════════════ */

    /* ── Total crides API d'un projecte ── */
    public function countByProject(int $projectId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('IDENTITY(r.project) = :pid')
            ->setParameter('pid', $projectId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* ── Crides per rang de status d'un projecte ── */
    public function countByProjectAndStatus(int $projectId, int $min, int $max): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('IDENTITY(r.project) = :pid')
            ->andWhere('r.statusCode >= :min')
            ->andWhere('r.statusCode <= :max')
            ->setParameter('pid', $projectId)
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* ── Endpoint més usat d'un projecte ── */
    public function topEndpointByProject(int $projectId): ?string
    {
        $result = $this->createQueryBuilder('r')
            ->select('r.endpoint, COUNT(r.id) AS total')
            ->where('IDENTITY(r.project) = :pid')
            ->groupBy('r.endpoint')
            ->orderBy('total', 'DESC')
            ->setParameter('pid', $projectId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        return $result['endpoint'] ?? null;
    }

    /* ── Temps de resposta mig d'un projecte (ms) ── */
    public function averageResponseTimeByProject(int $projectId): int
    {
        $result = (int) $this->createQueryBuilder('r')
            ->select('AVG(r.responseTimeMs)')
            ->where('IDENTITY(r.project) = :pid')
            ->andWhere('r.responseTimeMs IS NOT NULL')
            ->setParameter('pid', $projectId)
            ->getQuery()
            ->getSingleScalarResult();
        return $result;
    }

    /* ── Token grants d'un projecte (des del seu endpoint) ── */
    public function countTokenGrantsByProject(int $projectId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('IDENTITY(r.project) = :pid')
            ->andWhere('r.endpoint = :ep')
            ->andWhere('r.statusCode = 200')
            ->setParameter('pid', $projectId)
            ->setParameter('ep', '/api/public/token')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /* ── Token denials d'un projecte ── */
    public function countTokenDenialsByProject(int $projectId): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('IDENTITY(r.project) = :pid')
            ->andWhere('r.endpoint = :ep')
            ->andWhere('r.statusCode = 403')
            ->setParameter('pid', $projectId)
            ->setParameter('ep', '/api/public/token')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
