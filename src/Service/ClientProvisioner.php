<?php

/* ═══════════════════════════════════════════════════════════════════════
   ClientProvisioner — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Servei que auto-crea els content types base per a un client nou.
   Cada client rep "Notícies" (slug: noticia) i "Events" (slug: event)
   amb els seus camps predefinits, marcats com a base = true.
   ═══════════════════════════════════════════════════════════════════════ */

namespace App\Service;

use App\Entity\Client;
use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use Doctrine\ORM\EntityManagerInterface;

class ClientProvisioner
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {}

    /* ═══ Provisionar un client amb els seus content types base ═══ */
    public function provision(Client $client): void
    {
        /* ----- Notícies (slug: noticia) ----- */
        $noticies = $this->createNoticies($client);
        $client->addContentType($noticies);

        /* ----- Events (slug: event) ----- */
        $events = $this->createEvents($client);
        $client->addContentType($events);

        $this->em->flush();
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
            ['name' => 'Descripció',  'slug' => 'descripcio',  'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => true,  'sortOrder' => 1],
            ['name' => 'Imatge',      'slug' => 'imatge',      'type' => FieldDefinition::TYPE_IMAGE,    'required' => false, 'sortOrder' => 2],
            ['name' => 'Data event',  'slug' => 'data_event',  'type' => FieldDefinition::TYPE_DATE,     'required' => true,  'sortOrder' => 3],
            ['name' => 'Hora',        'slug' => 'hora',        'type' => FieldDefinition::TYPE_TEXT,     'required' => false, 'sortOrder' => 4],
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
