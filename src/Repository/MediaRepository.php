<?php

/* ===========================================================
   MediaRepository — Operacions sobre fitxers multimèdia.
   =========================================================== */

namespace App\Repository;

use App\Entity\Media;
use App\Entity\User;
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

    /* -----------------------------------------------------------
       findAllWithUserOrdered — Retorna tots els Media amb User
       i Project join, ordenats per company → project → createdAt.
       ----------------------------------------------------------- */
    public function findAllWithUserOrdered(): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.user', 'u')
            ->leftJoin('m.project', 'p')
            ->orderBy('u.company', 'ASC')
            ->addOrderBy('u.name', 'ASC')
            ->addOrderBy('p.name', 'ASC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /* -----------------------------------------------------------
       findByUserProjects — Retorna tot Media el projecte del qual
       pertany a l'usuari donat (independentment de qui el va pujar).
       ----------------------------------------------------------- */
    public function findByUserProjects(User $user): array
    {
        return $this->createQueryBuilder('m')
            ->join('m.project', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('p.name', 'ASC')
            ->addOrderBy('m.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
