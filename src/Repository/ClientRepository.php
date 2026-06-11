<?php

namespace App\Repository;

use App\Entity\Client;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ClientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Client::class);
    }

    public function findBySlug(string $slug): ?Client
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function findActive(): array
    {
        return $this->findBy(['active' => true], ['name' => 'ASC']);
    }
}
