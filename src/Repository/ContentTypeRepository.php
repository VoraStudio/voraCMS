<?php

/* ===========================================================
   ContentTypeRepository — Gestió de tipus de contingut amb
   tenant isolation. Totes les queries scopen pel client
   actual via ClientScope, excepte findBaseByClient que rep
   el Client explícitament (usat pel provisioner a Phase 6).
   =========================================================== */

namespace App\Repository;

use App\Entity\Client;
use App\Entity\ContentType;
use App\Service\ClientScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContentTypeRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClientScope $clientScope,
    ) {
        parent::__construct($registry, ContentType::class);
    }

    /* -----------------------------------------------------------
       findBySlug — Cerca un ContentType pel seu slug, filtrant
       pel client actual. Si no hi ha client (super-admin),
       retorna el primer amb aquest slug (global search).
       ----------------------------------------------------------- */
    public function findBySlug(string $slug): ?ContentType
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.slug = :slug')
            ->setParameter('slug', $slug);

        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(ct.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /* -----------------------------------------------------------
       findActive — Retorna tots els ContentType actius del client
       actual, ordenats alfabèticament per nom.
       ----------------------------------------------------------- */
    public function findActive(): array
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.active = :active')
            ->setParameter('active', true)
            ->orderBy('ct.name', 'ASC');

        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(ct.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        return $qb->getQuery()->getResult();
    }

    /* -----------------------------------------------------------
       findBaseByClient — Retorna els ContentType marcats com a
       base (base=true) per a un client específic.
       Usat per ClientProvisioner per verificar si un client
       ja té els tipus base provisionats.
       Ordenat per nom ASC per consistència.
       ----------------------------------------------------------- */
    public function findBaseByClient(Client $client): array
    {
        return $this->findBy(
            ['client' => $client, 'base' => true],
            ['name' => 'ASC']
        );
    }
}
