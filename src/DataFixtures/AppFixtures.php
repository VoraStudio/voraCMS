<?php

/* ═══════════════════════════════════════════════════════════════════════
   AppFixtures — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Crea els clients i usuaris per defecte per a desenvolupament:
     - Admin global (ROLE_ADMIN)
     - Client VoraStudio amb usuari MOD i un projecte d'exemple
   S'executa amb: php bin/console doctrine:fixtures:load
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
        /* ----- Client per defecte ----- */
        $defaultClient = new Client();
        $defaultClient->setName('Default');
        $defaultClient->setSlug('default');
        $defaultClient->setActive(true);
        $manager->persist($defaultClient);

        /* ----- Usuari admin ----- */
        $admin = new User();
        $admin->setEmail('admin@vorastudio.cat');
        $admin->setName('Admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'admin'));
        $admin->setClient($defaultClient);
        $manager->persist($admin);

        /* ----- Client VoraStudio ----- */
        $voraClient = new Client();
        $voraClient->setName('VoraStudio');
        $voraClient->setSlug('vorastudio');
        $voraClient->setActive(true);
        $manager->persist($voraClient);

        /* ----- Usuari MOD del client ----- */
        $voraUser = new User();
        $voraUser->setEmail('vora@vora.es');
        $voraUser->setName('vora');
        $voraUser->setRoles(['ROLE_MOD']);
        $voraUser->setPassword($this->hasher->hashPassword($voraUser, 'vora'));
        $voraUser->setClient($voraClient);
        $manager->persist($voraUser);

        /* ----- Usuari USUARIO bàsic del client (només entries + media) ----- */
        $basicUser = new User();
        $basicUser->setEmail('user@vora.es');
        $basicUser->setName('user');
        $basicUser->setRoles(['ROLE_MOD']);
        $basicUser->setPassword($this->hasher->hashPassword($basicUser, 'user'));
        $basicUser->setClient($voraClient);
        $manager->persist($basicUser);

        /* ----- Projecte d'exemple ----- */
        $project = new Project();
        $project->setName('Web Corporativa');
        $project->setSlug('web-corporativa');
        $project->setDescription('Projecte d\'exemple per al client VoraStudio');
        $project->setActive(true);
        $project->setClient($voraClient);
        $manager->persist($project);

        /* ----- Segon projecte per provar permisos diferents ----- */
        $project2 = new Project();
        $project2->setName('Botiga Online');
        $project2->setSlug('botiga-online');
        $project2->setDescription('Segon projecte per provar restriccions de permisos');
        $project2->setActive(true);
        $project2->setColor('#10B981');
        $project2->setClient($voraClient);
        $manager->persist($project2);

        /* ----- Permís de gestió de tipus de contingut (MOD al projecte 1) ----- */
        $userProject = new UserProject();
        $userProject->setUser($voraUser);
        $userProject->setProject($project);
        $userProject->setCanManageContentTypes(true);
        $manager->persist($userProject);

        /* ----- MOD al projecte 2 sense canManageContentTypes (usa el default) ----- */
        $userProject2 = new UserProject();
        $userProject2->setUser($voraUser);
        $userProject2->setProject($project2);
        $userProject2->setCanManageContentTypes(false);
        $manager->persist($userProject2);

        /* ----- USUARIO al projecte 1 amb permís elevat de CT ----- */
        $basicUserProject = new UserProject();
        $basicUserProject->setUser($basicUser);
        $basicUserProject->setProject($project);
        $basicUserProject->setCanManageContentTypes(true);
        $manager->persist($basicUserProject);

        $manager->flush();
    }
}
