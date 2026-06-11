<?php

/* ===========================================================
   UserRepository — Cerca d'usuaris amb tenant isolation.
   El ClientScope injectat resol el client_id del request
   actual (JWT, sessió, o explícit). Quan no hi ha client
   (super-admin), no s'aplica cap filtre addicional.
   =========================================================== */

namespace App\Repository;

use App\Entity\User;
use App\Service\ClientScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(
        ManagerRegistry $registry,
        private readonly ClientScope $clientScope,
    ) {
        parent::__construct($registry, User::class);
    }

    /* -----------------------------------------------------------
       upgradePassword — Requerit per Symfony security
       ----------------------------------------------------------- */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException();
        }
        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->flush();
    }

    /* -----------------------------------------------------------
       findByEmail — Cerca usuari per email dins del client actual.
       Fem servir QueryBuilder explícit perquè el filtre Doctrine
       pot no estar actiu (ex: durant el login abans que
       ClientFilterSubscriber estableixi el paràmetre).
       ----------------------------------------------------------- */
    public function findByEmail(string $email): ?User
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email);

        /* Si hi ha un client_id actiu, afegim el filtre manualment.
           Si és null (super-admin), cap WHERE extra → veu tots els usuaris. */
        $clientId = $this->clientScope->getClientId();
        if ($clientId !== null) {
            $qb->andWhere('IDENTITY(u.client) = :clientId')
                ->setParameter('clientId', $clientId);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /* -----------------------------------------------------------
       findByEmailAndClient — Cerca cross-client explícita.
       Útil per a queries que necessiten un client concret sense
       dependre del ClientScope (ex: login abans de tenir sessió).
       ----------------------------------------------------------- */
    public function findByEmailAndClient(string $email, int $clientId): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->andWhere('IDENTITY(u.client) = :clientId')
            ->setParameter('email', $email)
            ->setParameter('clientId', $clientId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
