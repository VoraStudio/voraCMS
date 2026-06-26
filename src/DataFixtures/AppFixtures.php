<?php

/* ═══════════════════════════════════════════════════════════════════════
   AppFixtures — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Usuaris base per a desenvolupament:
     - admin@vora.es  → ROLE_ADMIN
     - vora@vora.es   → ROLE_MOD
     - palmito@vora.es → ROLE_USER (se li assigna ROLE_USUARIO automàticament)
   Tots amb contrasenya: 123
   ═══════════════════════════════════════════════════════════════════════ */

namespace App\DataFixtures;

use App\Entity\Client;
use App\Entity\Project;
use App\Entity\User;
use App\Entity\UserProject;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher) {}

    public function load(ObjectManager $manager): void
    {
        /* ----- Client principal ----- */
        $client = new Client();
        $client->setName('VoraStudio');
        $client->setSlug('vorastudio');
        $client->setActive(true);
        $manager->persist($client);

        /* ----- admin@vora.es (ROLE_ADMIN) ----- */
        $admin = new User();
        $admin->setEmail('admin@vora.es');
        $admin->setName('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, '123'));
        $admin->setClient($client);
        $manager->persist($admin);

        /* ----- vora@vora.es (ROLE_MOD) ----- */
        $mod = new User();
        $mod->setEmail('vora@vora.es');
        $mod->setName('Vora');
        $mod->setRoles(['ROLE_MOD']);
        $mod->setPassword($this->hasher->hashPassword($mod, '123'));
        $mod->setClient($client);
        $manager->persist($mod);

        /* ----- palmito@vora.es (ROLE_USER) ----- */
        /* Nota: User::getRoles() afegeix ROLE_USUARIO automàticament */
        $user = new User();
        $user->setEmail('palmito@vora.es');
        $user->setName('Palmito');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->hasher->hashPassword($user, '123'));
        $user->setClient($client);
        $manager->persist($user);

        /* ----- Projecte d'exemple ----- */
        $project = new Project();
        $project->setName('Web Corporativa');
        $project->setSlug('web-corporativa');
        $project->setDescription('Projecte d\'exemple per testejar');
        $project->setActive(true);
        $project->setClient($client);
        $manager->persist($project);

        /* ----- Permisos: MOD pot gestionar content types ----- */
        $modProject = new UserProject();
        $modProject->setUser($mod);
        $modProject->setProject($project);
        $modProject->setCanManageContentTypes(true);
        $manager->persist($modProject);

        /* ----- Permisos: USER bàsic només pot veure ----- */
        $userProject = new UserProject();
        $userProject->setUser($user);
        $userProject->setProject($project);
        $userProject->setCanManageContentTypes(false);
        $manager->persist($userProject);

        $manager->flush();
    }
}
