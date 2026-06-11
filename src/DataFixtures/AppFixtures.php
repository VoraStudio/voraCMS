<?php

/* ═══════════════════════════════════════════════════════════════════════
   AppFixtures — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Crea el client per defecte (Default) i l'usuari super-admin associat.
   S'executa amb: php bin/console doctrine:fixtures:load
   ═══════════════════════════════════════════════════════════════════════ */

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        /* ----- Client per defecte ----- */
        $defaultClient = new Client();
        $defaultClient->setName('Default');
        $defaultClient->setSlug('default');
        $defaultClient->setActive(true);
        $manager->persist($defaultClient);

        /* ----- Usuari super-admin ----- */
        $admin = new User();
        $admin->setEmail('admin@vorastudio.cat');
        $admin->setName('Admin');
        $admin->setRoles(['ROLE_SUPER_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));
        $admin->setClient($defaultClient);
        $manager->persist($admin);

        $manager->flush();
    }
}
