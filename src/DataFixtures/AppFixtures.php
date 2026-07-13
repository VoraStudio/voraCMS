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
use App\Entity\FieldDefinition;
use App\Entity\Project;
use App\Entity\User;
use App\Service\SlugGenerator;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $hasher,
        private SlugGenerator $slugGenerator,
    ) {}

    public function load(ObjectManager $manager): void
    {
        /* ----- admin@vora.es (ROLE_ADMIN) ----- */
        $admin = new User();
        $admin->setEmail('admin@vora.es');
        $admin->setName('Vora Studio');
        $admin->setSlug($this->slugGenerator->generate('vora-studio'));
        $admin->setCompany('Vora Studio');
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
        $palmito->setRoles(['ROLE_USUARIO']);
        $palmito->setPassword($this->hasher->hashPassword($palmito, '123'));
        $palmito->setActive(true);
        $manager->persist($palmito);

        /* ----- Projecte base: admin ----- */
        $landings = new Project();
        $landings->setName('Web');
        $landings->setSlug('web');
        $landings->setDescription('Projecte base de la web');
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

        /* ----- Plantilles base (es clonen en crear projecte) ----- */
        $noticies = $this->createNoticiesTemplate($admin);
        $manager->persist($noticies);

        $events = $this->createEventsTemplate($admin);
        $manager->persist($events);

        /* ----- ContentTypes per als projectes existents (clonant plantilles) ----- */
        foreach ([$landings, $webPrincipal] as $project) {
            $ctNoticies = $this->cloneTemplate($noticies, $project, $manager);
            $ctEvents = $this->cloneTemplate($events, $project, $manager);
            $manager->persist($ctNoticies);
            $manager->persist($ctEvents);
        }

        $manager->flush();
    }

    private function createNoticiesTemplate(User $admin): ContentType
    {
        $ct = new ContentType();
        $ct->setName('Notícies');
        $ct->setSlug('noticia');
        $ct->setDescription('Articles i notícies del projecte');
        $ct->setBase(true);
        $ct->setActive(true);
        $ct->setUser($admin);

        $fields = [
            ['Títol', 'titul', 'text', true, 0],
            ['Descripció', 'descripcio', 'richtext', true, 1],
            ['Data', 'data', 'date', false, 2],
            ['Ubicació', 'location', 'text', false, 3],
            ['Imatge', 'imatge', 'image', false, 4],
        ];

        foreach ($fields as $f) {
            $fd = new FieldDefinition();
            $fd->setName($f[0]);
            $fd->setSlug($f[1]);
            $fd->setFieldType($f[2]);
            $fd->setRequired($f[3]);
            $fd->setSortOrder($f[4]);
            $ct->addField($fd);
        }

        return $ct;
    }

    private function createEventsTemplate(User $admin): ContentType
    {
        $ct = new ContentType();
        $ct->setName('Events');
        $ct->setSlug('event');
        $ct->setDescription('Esdeveniments i actes');
        $ct->setBase(true);
        $ct->setActive(true);
        $ct->setUser($admin);

        $fields = [
            ['Títol', 'titul', 'text', true, 0],
            ['Subtítol', 'subtitol', 'text', false, 1],
            ['Descripció', 'descripcio', 'richtext', true, 2],
            ['Data', 'data', 'date', true, 3],
            ['Ubicació', 'location', 'text', false, 4],
        ];

        foreach ($fields as $f) {
            $fd = new FieldDefinition();
            $fd->setName($f[0]);
            $fd->setSlug($f[1]);
            $fd->setFieldType($f[2]);
            $fd->setRequired($f[3]);
            $fd->setSortOrder($f[4]);
            $ct->addField($fd);
        }

        return $ct;
    }

    private function cloneTemplate(ContentType $template, Project $project, ObjectManager $manager): ContentType
    {
        $ct = new ContentType();
        $ct->setName($template->getName());
        $ct->setSlug($template->getSlug());
        $ct->setDescription($template->getDescription());
        $ct->setActive(true);
        $ct->setBase(false);
        $ct->setUser($project->getUser());
        $ct->setProject($project);

        foreach ($template->getFields() as $field) {
            $fd = new FieldDefinition();
            $fd->setName($field->getName());
            $fd->setSlug($field->getSlug());
            $fd->setFieldType($field->getFieldType());
            $fd->setRequired($field->isRequired());
            $fd->setSortOrder($field->getSortOrder());
            $ct->addField($fd);
        }

        return $ct;
    }
}
