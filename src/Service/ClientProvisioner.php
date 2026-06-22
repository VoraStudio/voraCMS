<?php

/* ═══════════════════════════════════════════════════════════════════════
   ClientProvisioner — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Servei que auto-crea els content types base i l'usuari administrador
   per a un client nou. Cada client rep:
     - "Notícies" (slug: noticia) amb camps predefinits
     - "Events"   (slug: event)   amb camps predefinits
     - 1 usuari administrador amb el rol especificat
   ═══════════════════════════════════════════════════════════════════════ */

namespace App\Service;

use App\Entity\Client;
use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ClientProvisioner
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    /* ═══ Provisionar només content types (per compatibilitat) ═══ */
    public function provision(Client $client): void
    {
        $this->createContentTypes($client);
        $this->em->flush();
    }

    /* ═══ Provisionar content types + usuari administrador ═══ */
    public function provisionWithUser(Client $client, string $email, string $password, string $role): User
    {
        $this->createContentTypes($client);

        /* Creem l'usuari administrador d'aquest client.
           Com que cada client té exactament 1 usuari, l'email
           ha de ser únic dins del client (unique constraint). */
        $user = new User();
        $user->setEmail($email);
        $user->setName($client->getName());
        $user->setLocale('ca');
        $user->setActive(true);
        $user->setClient($client);
        $user->setRoles([$role]);

        $hashedPassword = $this->passwordHasher->hashPassword($user, $password);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    /* ═══ Crear els content types base ═══ */
    private function createContentTypes(Client $client): void
    {
        $noticies = $this->createNoticies($client);
        $client->addContentType($noticies);
        $this->em->persist($noticies);

        $events = $this->createEvents($client);
        $client->addContentType($events);
        $this->em->persist($events);
    }

    /* ═══ Content Type: Notícies ═══ */
    private function createNoticies(Client $client): ContentType
    {
        $ct = new ContentType();
        $ct->setName('Notícies');
        $ct->setSlug('noticia');
        $ct->setBase(true);
        $ct->setActive(true);
        $ct->setClient($client);

        $fields = [
            ['name' => 'Títol',      'slug' => 'titul',      'type' => FieldDefinition::TYPE_TEXT,     'required' => true,  'sortOrder' => 0],
            ['name' => 'Descripció', 'slug' => 'descripcio',  'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => true,  'sortOrder' => 1],
            ['name' => 'Imatge',     'slug' => 'imatge',      'type' => FieldDefinition::TYPE_IMAGE,    'required' => false, 'sortOrder' => 2],
            ['name' => 'Data',       'slug' => 'data',        'type' => FieldDefinition::TYPE_DATE,     'required' => false, 'sortOrder' => 3],
            ['name' => 'Contingut',  'slug' => 'contingut',   'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => false, 'sortOrder' => 4],
        ];

        foreach ($fields as $f) {
            $fd = new FieldDefinition();
            $fd->setName($f['name']);
            $fd->setSlug($f['slug']);
            $fd->setFieldType($f['type']);
            $fd->setRequired($f['required']);
            $fd->setSortOrder($f['sortOrder']);
            $ct->addField($fd);
        }

        return $ct;
    }

    /* ═══ Content Type: Events ═══ */
    private function createEvents(Client $client): ContentType
    {
        $ct = new ContentType();
        $ct->setName('Events');
        $ct->setSlug('event');
        $ct->setBase(true);
        $ct->setActive(true);
        $ct->setClient($client);

        $fields = [
            ['name' => 'Títol',       'slug' => 'titul',      'type' => FieldDefinition::TYPE_TEXT,     'required' => true,  'sortOrder' => 0],
            ['name' => 'Subtítol',    'slug' => 'subtitol',    'type' => FieldDefinition::TYPE_TEXT,     'required' => false, 'sortOrder' => 1],
            ['name' => 'Descripció',  'slug' => 'descripcio',  'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => true,  'sortOrder' => 2],
            ['name' => 'Imatge',      'slug' => 'imatge',      'type' => FieldDefinition::TYPE_IMAGE,    'required' => false, 'sortOrder' => 3],
            ['name' => 'Data event',  'slug' => 'data_event',  'type' => FieldDefinition::TYPE_DATE,     'required' => true,  'sortOrder' => 4],
            ['name' => 'Ubicació',    'slug' => 'ubicacio',    'type' => FieldDefinition::TYPE_TEXT,     'required' => false, 'sortOrder' => 5],
            ['name' => 'Enllaç',      'slug' => 'enllac',      'type' => FieldDefinition::TYPE_URL,      'required' => false, 'sortOrder' => 6],
        ];

        foreach ($fields as $f) {
            $fd = new FieldDefinition();
            $fd->setName($f['name']);
            $fd->setSlug($f['slug']);
            $fd->setFieldType($f['type']);
            $fd->setRequired($f['required']);
            $fd->setSortOrder($f['sortOrder']);
            $ct->addField($fd);
        }

        return $ct;
    }
}
