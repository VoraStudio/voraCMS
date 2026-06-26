<?php

/* ===========================================================
   EntryRepository — Consultes d'entrades.
   =========================================================== */

namespace App\Repository;

use App\Entity\Entry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Entry::class);
    }

    public function findPublishedByType(string $contentTypeSlug, ?string $locale = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.contentType', 'ct')
            ->where('ct.slug = :slug')
            ->andWhere('e.status = :status')
            ->setParameter('slug', $contentTypeSlug)
            ->setParameter('status', Entry::STATUS_PUBLISHED)
            ->orderBy('e.createdAt', 'DESC');

        if ($locale) {
            $qb->andWhere('e.locale = :locale')
                ->setParameter('locale', $locale);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPublishedById(int $id): ?Entry
    {
        return $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->andWhere('e.status = :status')
            ->setParameter('id', $id)
            ->setParameter('status', Entry::STATUS_PUBLISHED)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findLatestPublished(int $limit = 10): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.status = :status')
            ->setParameter('status', Entry::STATUS_PUBLISHED)
            ->orderBy('e.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countPublishedByUser(int $userId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.status = :status')
            ->andWhere('IDENTITY(e.user) = :userId')
            ->setParameter('status', Entry::STATUS_PUBLISHED)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTodayByUser(int $userId): int
    {
        $today = new \DateTimeImmutable('today');

        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.createdAt >= :today')
            ->andWhere('IDENTITY(e.user) = :userId')
            ->setParameter('today', $today)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
