<?php

/* ===========================================================
   MediaRepository — Operacions sobre fitxers multimèdia
   amb tenant isolation. Mètodes findByClient* accepten
   clientId explícit perquè els controladors ja saben
   quin client estan gestionant.
   =========================================================== */

namespace App\Repository;

use App\Entity\Media;
use App\Service\ClientScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MediaRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClientScope $clientScope,
    ) {
        parent::__construct($registry, Media::class);
    }

    /* -----------------------------------------------------------
       findByClient — Retorna tot el media d'un client concret.
       Sense ordenació garantida (per queries internes ràpides).
       ----------------------------------------------------------- */
    public function findByClient(int $clientId): array
    {
        return $this->findBy(['client' => $clientId]);
    }

    /* -----------------------------------------------------------
       findByClientOrdered — Retorna media d'un client ordenat
       per data de creació descendent (més recent primer).
       Útil per al panell d'administració i el picker de media.
       ----------------------------------------------------------- */
    public function findByClientOrdered(int $clientId): array
    {
        return $this->findBy(
            ['client' => $clientId],
            ['createdAt' => 'DESC']
        );
    }
}
