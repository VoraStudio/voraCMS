<?php

/* ═══════════════════════════════════════════════════════════════════════
   AppFixtures — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Usuaris base per a desenvolupament. Ara l'usuari és el tenant.
     - admin@vora.es  → ROLE_ADMIN
     - palmito@vora.es → ROLE_USUARIO
   Tots amb contrasenya definida i projecte base.
   ═══════════════════════════════════════════════════════════════════════ */

namespace App\DataFixtures;

use App\Entity\ContentType;
use App\Entity\Project;
use App\Entity\User;
use App\Service\SlugGenerator;
use App\Service\TokenGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private SlugGenerator $slugGenerator,
        private TokenGenerator $tokenGenerator,
    ) {}

    public function load(ObjectManager $manager): void
    {
        /* ----- admin@vora.es (ROLE_ADMIN) ----- */
        $admin = new User();
        $admin->setEmail('admin@vora.es');
        $admin->setName('Vora Studio');
        $admin->setSlug($this->slugGenerator->generate('vora-studio'));
        $admin->setCompany('Vora Studio');
        $admin->setApiToken($this->tokenGenerator->generate(32));
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, '123'));
        $admin->setActive(true);
        $manager->persist($admin);

        /* ----- palmito@vora.es (ROLE_USUARIO) ----- */
        $palmito = new User();
        $palmito->setEmail('palmito@vora.es');
        $palmito->setName('Palmito House');
        $palmito->setSlug($this->slugGenerator->generate('palmito-house'));
        $palmito->setCompany('Palmito House');
        $palmito->setApiToken($this->tokenGenerator->generate(32));
        $palmito->setRoles(['ROLE_USUARIO']);
        $palmito->setPassword($this->hasher->hashPassword($palmito, '123'));
        $palmito->setActive(true);
        $manager->persist($palmito);

        /* ----- Projecte base: admin ----- */
        $landings = new Project();
        $landings->setName('Landings');
        $landings->setSlug('landings');
        $landings->setDescription('Projecte base de landings');
        $landings->setColor('#4945FF');
        $landings->setActive(true);
        $landings->setUser($admin);
        $manager->persist($landings);

        /* ----- Projecte base: palmito ----- */
        $webPrincipal = new Project();
        $webPrincipal->setName('Web Principal');
        $webPrincipal->setSlug('web-principal');
        $webPrincipal->setDescription('Web principal de Palmito House');
        $webPrincipal->setColor('#10B981');
        $webPrincipal->setActive(true);
        $webPrincipal->setUser($palmito);
        $manager->persist($webPrincipal);

        /* ----- ContentTypes per a cada projecte ----- */
        foreach ([$landings, $webPrincipal] as $project) {
            $noticies = new ContentType();
            $noticies->setName('Noticies');
            $noticies->setSlug('noticies');
            $noticies->setDescription('Notícies i actualitzacions');
            $noticies->setActive(true);
            $noticies->setBase(true);
            $noticies->setUser($project->getUser());
            $noticies->setProject($project);
            $manager->persist($noticies);

            $events = new ContentType();
            $events->setName('Events');
            $events->setSlug('events');
            $events->setDescription('Esdeveniments i actes');
            $events->setActive(true);
            $events->setBase(true);
            $events->setUser($project->getUser());
            $events->setProject($project);
            $manager->persist($events);
        }

        $manager->flush();
    }
}
