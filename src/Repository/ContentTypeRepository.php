<?php

/* ===========================================================
   ContentTypeRepository — Gestió de tipus de contingut amb
   tenant isolation. Filtra per user_id (el propietari).
   =========================================================== */

namespace App\Repository;

use App\Entity\ContentType;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContentTypeRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, ContentType::class);
    }

    public function findBySlug(string $slug, ?int $projectId = null): ?ContentType
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.slug = :slug')
            ->setParameter('slug', $slug);

        if ($projectId !== null) {
            $qb->andWhere('ct.project = :project')
                ->setParameter('project', $projectId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    public function findActive(?int $projectId = null): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.active = :active')
            ->setParameter('active', true)
            ->orderBy('ct.name', 'ASC');

        if ($projectId !== null) {
            $qb->andWhere('ct.project = :project')
                ->setParameter('project', $projectId);
        } else {
            $qb->andWhere('ct.project IS NULL')
                ->andWhere('ct.base = :base')
                ->setParameter('base', false);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBaseByUser(User $user): array
    {
        return $this->findBy(
            ['user' => $user, 'base' => true],
            ['name' => 'ASC']
        );
    }

    /**
     * @return ContentType[]
     */
    public function findLatestWithProject(int $limit = 5): array
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.project IS NOT NULL')
            ->orderBy('ct.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ContentType[]
     */
    public function findAllForAdmin(?int $projectId = null): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->orderBy('ct.base', 'DESC')
            ->addOrderBy('ct.name', 'ASC');

        if ($projectId !== null) {
            $qb->where('ct.project = :project OR (ct.base = :base AND ct.project IS NULL)')
               ->setParameter('project', $projectId)
               ->setParameter('base', true);
        }

        return $qb->getQuery()->getResult();
    }

    public function findBaseTemplates(): array
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.base = :base')
            ->andWhere('ct.project IS NULL')
            ->setParameter('base', true)
            ->orderBy('ct.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ContentType[]
     */
    public function findAutoCloneTemplates(): array
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.base = :base')
            ->andWhere('ct.project IS NULL')
            ->andWhere('ct.autoClone = :autoClone')
            ->setParameter('base', true)
            ->setParameter('autoClone', true)
            ->orderBy('ct.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return ContentType[]
     */
    public function findByUser(int $userId, ?bool $active = true): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->andWhere('ct.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('ct.name', 'ASC');

        if ($active !== null) {
            $qb->andWhere('ct.active = :active')
               ->setParameter('active', $active);
        }

        return $qb->getQuery()->getResult();
    }
}
