<?php

/* ===========================================================
   ContentTypeRepository — Gestió de tipus de contingut amb
   tenant isolation + project isolation.

   Totes les queries scopen pel client actual via ClientScope.
   Quan es passa un project_id, filtren també per projecte.
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
       pel client actual i (si es passa) pel projecte.
       ----------------------------------------------------------- */
    public function findBySlug(string $slug, ?int $projectId = null): ?ContentType
    {
        $qb = $this->createQueryBuilder('ct')
            ->where('ct.slug = :slug')
            ->setParameter('slug', $slug);

        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(ct.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        if ($projectId !== null) {
            $qb->andWhere('IDENTITY(ct.project) = :projectId OR ct.project IS NULL')
                ->setParameter('projectId', $projectId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /* -----------------------------------------------------------
       findActive — Retorna els ContentType actius del client
       actual. Si es passa projectId, només els d'aquest projecte.
       ----------------------------------------------------------- */
    public function findActive(?int $projectId = null): array
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

        if ($projectId !== null) {
            /* Filtra els ContentTypes del projecte actiu més els base (project IS NULL) */
            $qb->andWhere('IDENTITY(ct.project) = :projectId OR ct.project IS NULL')
                ->setParameter('projectId', $projectId);
        } else {
            /* Si no hi ha projecte, només els que NO pertanyen a cap (legacy) */
            $qb->andWhere('ct.project IS NULL');
        }

        return $qb->getQuery()->getResult();
    }

    /* -----------------------------------------------------------
       findBaseByClient — Retorna els ContentType marcats com a
       base (base=true) per a un client específic.
       Usat per ClientProvisioner.
       ----------------------------------------------------------- */
    public function findBaseByClient(Client $client): array
    {
        return $this->findBy(
            ['client' => $client, 'base' => true],
            ['name' => 'ASC']
        );
    }
}
