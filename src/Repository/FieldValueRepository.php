<?php

namespace App\Repository;

use App\Entity\FieldValue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FieldValueRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FieldValue::class);
    }

    public function findByEntry(int $entryId): array
    {
        return $this->findBy(['entry' => $entryId]);
    }
}
