<?php

/* ===========================================================
   UserRepository — Cerca d'usuaris.
   =========================================================== */

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    public function findByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findBySlug(string $slug): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /* ── Troba usuari amb allowed_domains que contingui el domini ── */
    public function findOneByDomain(string $domain): ?User
    {
        return $this->createQueryBuilder('u')
            ->where($this->getEntityManager()->getExpressionBuilder()->like(
                'u.allowedDomains',
                ':domain'
            ))
            ->setParameter('domain', '%"' . $domain . '"%')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
