<?php

/* ===========================================================
   MediaRepository — Operacions sobre fitxers multimèdia.
   =========================================================== */

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MediaRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Media::class);
    }

    public function findByUser(int $userId): array
    {
        return $this->findBy(['user' => $userId]);
    }

    public function findByUserOrdered(int $userId): array
    {
        return $this->findBy(
            ['user' => $userId],
            ['createdAt' => 'DESC']
        );
    }
}
