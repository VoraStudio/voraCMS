<?php

/* ===========================================================
   ContentTypeRepository — Gestió de tipus de contingut amb
   tenant isolation. Filtra per user_id (el propietari).
   =========================================================== */

namespace App\Repository;

use App\Entity\ContentType;
use App\Entity\Project;
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
    public function findAllForAdmin(): array
    {
        $em = $this->getEntityManager();

        // Subquery: slugs of all base templates
        $subQb = $em->createQueryBuilder()
            ->select('b.slug')
            ->from(ContentType::class, 'b')
            ->where('b.base = :baseTrue');

        // 1) Base templates
        $base = $this->createQueryBuilder('ct')
            ->addSelect('p')
            ->leftJoin('ct.project', 'p')
            ->where('ct.base = :baseTrue')
            ->setParameter('baseTrue', true)
            ->orderBy('ct.name', 'ASC')
            ->getQuery()
            ->getResult();

        // 2) Custom types: non-base, slug doesn't match any base template
        $custom = $this->createQueryBuilder('ct2')
            ->addSelect('p2')
            ->leftJoin('ct2.project', 'p2')
            ->where('ct2.base != :baseTrue')
            ->andWhere($em->getExpressionBuilder()->notIn('ct2.slug', $subQb->getDQL()))
            ->setParameter('baseTrue', true)
            ->orderBy('ct2.name', 'ASC')
            ->getQuery()
            ->getResult();

        // Deduplicate custom types by slug (keep first occurrence per slug)
        $seen = [];
        $deduped = [];
        foreach ($custom as $ct) {
            $slug = $ct->getSlug();
            if (!isset($seen[$slug])) {
                $seen[$slug] = true;
                $deduped[] = $ct;
            }
        }

        return array_merge($base, $deduped);
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
    public function findBaseByFeatures(array $features): array
    {
        return $this->createQueryBuilder('ct')
            ->where('ct.active = :active')
            ->andWhere('ct.base = :base')
            ->andWhere('ct.project IS NULL')
            ->andWhere('ct.feature IN (:features)')
            ->setParameter('active', true)
            ->setParameter('base', true)
            ->setParameter('features', $features)
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

    /**
     * Retorna els ContentTypes visibles per a un projecte sense duplicats.
     * Dona prioritat absoluta als ContentTypes propis del projecte,
     * i només afegeix plantilles base per a features no cobertes que
     * no comparteixin slug amb cap existent.
     *
     * @return ContentType[]
     */
    public function findForProject(Project $project): array
    {
        $features = $project->getContentFeatures();

        // 1. ContentTypes propis del projecte actius
        $projectTypes = $this->findActive($project->getId());
        $filtered = array_values(array_filter($projectTypes, function (ContentType $ct) use ($features) {
            return in_array($ct->getFeature(), $features, true);
        }));

        $existingSlugs = array_map(fn(ContentType $ct) => $ct->getSlug(), $filtered);
        $covered = array_unique(array_map(fn(ContentType $ct) => $ct->getFeature(), $filtered));
        $missing = array_diff($features, $covered);

        // 2. Fallback a plantilles base per a features no cobertes (evitant duplicats per slug)
        if (!empty($missing)) {
            $baseTypes = $this->findBaseByFeatures($missing);
            foreach ($baseTypes as $baseCt) {
                if (!in_array($baseCt->getSlug(), $existingSlugs, true)) {
                    $filtered[] = $baseCt;
                    $existingSlugs[] = $baseCt->getSlug();
                }
            }
        }

        // 3. Deduplicació final per slug (el propi del projecte sempre té prioritat)
        $seen = [];
        $deduped = [];
        foreach ($filtered as $ct) {
            $slug = $ct->getSlug();
            if (!isset($seen[$slug])) {
                $seen[$slug] = true;
                $deduped[] = $ct;
            }
        }

        return $deduped;
    }
}
