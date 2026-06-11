<?php

namespace App\Repository;

use App\Entity\FieldDefinition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FieldDefinitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FieldDefinition::class);
    }

    public function findByContentType(int $contentTypeId): array
    {
        return $this->findBy(['contentType' => $contentTypeId], ['sortOrder' => 'ASC']);
    }
}
