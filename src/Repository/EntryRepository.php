<?php

/* ===========================================================
   EntryRepository — Consultes d'entrades amb tenant isolation.
   Les entrades tenen client_id directe (defense in depth),
   no s'hereta via ContentType. Això permet filtrar sense JOIN
   i evita leaks si el ContentType perd la referència al client.

   Totes les queries públiques scopen pel client actual.
   =========================================================== */

namespace App\Repository;

use App\Entity\Entry;
use App\Service\ClientScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntryRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClientScope $clientScope,
    ) {
        parent::__construct($registry, Entry::class);
    }

    /* -----------------------------------------------------------
       findPublishedByType — Retorna entrades publicades d'un
       tipus de contingut (per slug), opcionalment filtrades
       per locale, i scoped al client actual.

       Fem servir e.client directe (no ct.client) perquè Entry
       té el seu propi client_id — defense in depth del design.
       ----------------------------------------------------------- */
    public function findPublishedByType(string $contentTypeSlug, ?string $locale = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->join('e.contentType', 'ct')
            ->where('ct.slug = :slug')
            ->andWhere('e.status = :status')
            ->setParameter('slug', $contentTypeSlug)
            ->setParameter('status', Entry::STATUS_PUBLISHED)
            ->orderBy('e.createdAt', 'DESC');

        /* Tenant isolation: filtrem per client_id de l'entrada.
           El JOIN a ContentType és només per filtrar pel slug. */
        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(e.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($locale) {
            $qb->andWhere('e.locale = :locale')
                ->setParameter('locale', $locale);
        }

        return $qb->getQuery()->getResult();
    }

    /* -----------------------------------------------------------
       findPublishedById — Retorna una entrada publicada pel seu
       ID, scoped al client actual. Usat per la ruta de detall.
       ----------------------------------------------------------- */
    public function findPublishedById(int $id): ?Entry
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.id = :id')
            ->andWhere('e.status = :status')
            ->setParameter('id', $id)
            ->setParameter('status', Entry::STATUS_PUBLISHED);

        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(e.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }
}
