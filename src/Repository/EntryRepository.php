<?php

namespace App\Repository;

use App\Entity\Entry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
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
            $qb->andWhere('e.locale = :locale')->setParameter('locale', $locale);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPublishedById(int $id): ?Entry
    {
        return $this->findOneBy(['id' => $id, 'status' => Entry::STATUS_PUBLISHED]);
    }
}
