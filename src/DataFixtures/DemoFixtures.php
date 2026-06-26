<?php

/* ===========================================================
   DemoFixtures — VoraCMS
   ===========================================================
   Crea els content types i dades demo per als projectes base.
   S'executa amb: php bin/console doctrine:fixtures:load

   Content types creats:
     - Notícies (slug: noticia) → camps: titul, descripcio, data, location, imatge
     - Events   (slug: event)   → camps: titul, descripcio, data, hora, location

   Entrades demo:
     - 1 notícia: "Exposició d'Art Contemporani 2026"
     - 1 event:   "Vernisage: Noves mirades"
   =========================================================== */

namespace App\DataFixtures;

use App\Entity\ContentType;
use App\Entity\Entry;
use App\Entity\FieldDefinition;
use App\Entity\FieldValue;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DemoFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin = $manager->getRepository(User::class)->findOneBy(['email' => 'admin@vora.es']);
        if (!$admin instanceof User) {
            return;
        }

        /* ===========================================================
           1. CONTENT TYPE: NOTÍCIES (slug: noticia)
              Camps: titul (text) · descripcio (richtext) · data (date)
                     location (text) · imatge (image)
              El frontend les consumeix a GET /api/noticia
           =========================================================== */
        $noticies = new ContentType();
        $noticies->setName('Notícies');
        $noticies->setSlug('noticia');
        $noticies->setDescription('Articles i notícies del projecte');
        $noticies->setBase(true);
        $noticies->setActive(true);
        $noticies->setUser($admin);

        $f1 = new FieldDefinition();
        $f1->setName('Títol');
        $f1->setSlug('titul');
        $f1->setFieldType('text');
        $f1->setRequired(true);
        $f1->setSortOrder(0);
        $noticies->addField($f1);

        $f2 = new FieldDefinition();
        $f2->setName('Descripció');
        $f2->setSlug('descripcio');
        $f2->setFieldType('richtext');
        $f2->setRequired(true);
        $f2->setSortOrder(1);
        $noticies->addField($f2);

        $f3 = new FieldDefinition();
        $f3->setName('Data');
        $f3->setSlug('data');
        $f3->setFieldType('date');
        $f3->setRequired(false);
        $f3->setSortOrder(2);
        $noticies->addField($f3);

        $f4 = new FieldDefinition();
        $f4->setName('Ubicació');
        $f4->setSlug('location');
        $f4->setFieldType('text');
        $f4->setRequired(false);
        $f4->setSortOrder(3);
        $noticies->addField($f4);

        $f5 = new FieldDefinition();
        $f5->setName('Imatge');
        $f5->setSlug('imatge');
        $f5->setFieldType(FieldDefinition::TYPE_IMAGE);
        $f5->setRequired(false);
        $f5->setSortOrder(4);
        $noticies->addField($f5);

        $manager->persist($noticies);

        /* ===========================================================
           2. CONTENT TYPE: EVENTS (slug: event)
              Camps: titul (text) · descripcio (richtext) · data (date)
                     hora (text) · location (text)
              El frontend les consumeix a GET /api/event
           =========================================================== */
        $events = new ContentType();
        $events->setName('Events');
        $events->setSlug('event');
        $events->setDescription('Esdeveniments i actes');
        $events->setBase(true);
        $events->setActive(true);
        $events->setUser($admin);

        $fe1 = new FieldDefinition();
        $fe1->setName('Títol');
        $fe1->setSlug('titul');
        $fe1->setFieldType('text');
        $fe1->setRequired(true);
        $fe1->setSortOrder(0);
        $events->addField($fe1);

        $feSub = new FieldDefinition();
        $feSub->setName('Subtítol');
        $feSub->setSlug('subtitol');
        $feSub->setFieldType('text');
        $feSub->setRequired(false);
        $feSub->setSortOrder(1);
        $events->addField($feSub);

        $fe2 = new FieldDefinition();
        $fe2->setName('Descripció');
        $fe2->setSlug('descripcio');
        $fe2->setFieldType('richtext');
        $fe2->setRequired(true);
        $fe2->setSortOrder(2);
        $events->addField($fe2);

        $fe3 = new FieldDefinition();
        $fe3->setName('Data');
        $fe3->setSlug('data');
        $fe3->setFieldType('date');
        $fe3->setRequired(true);
        $fe3->setSortOrder(3);
        $events->addField($fe3);

        $fe5 = new FieldDefinition();
        $fe5->setName('Ubicació');
        $fe5->setSlug('location');
        $fe5->setFieldType('text');
        $fe5->setRequired(false);
        $fe5->setSortOrder(4);
        $events->addField($fe5);

        $manager->persist($events);
        $manager->flush();

        /* ===========================================================
           3. ENTRADES DEMO
              Creem 1 notícia i 1 event per poder testejar l'API.
              Les imatges es deixen buides (es poden afegir des de l'admin).
           =========================================================== */
        $entry = new Entry();
        $entry->setContentType($noticies);
        $entry->setStatus(Entry::STATUS_PUBLISHED);
        $entry->setAuthor($admin);
        $entry->setUser($admin);
        $entry->setActive(true);
        $entry->setPublishedAt(new \DateTime());
        $this->addFieldValue($manager, $entry, $f1, 'Exposició d\'Art Contemporani 2026');
        $this->addFieldValue($manager, $entry, $f2, '<p>Gran exposició d\'art contemporani amb obres de diversos artistes internacionals.</p>');
        $this->addFieldValue($manager, $entry, $f3, '2026-06-15');
        $this->addFieldValue($manager, $entry, $f4, 'Girona');
        $this->addFieldValue($manager, $entry, $f5, '');
        $manager->persist($entry);

        $entry2 = new Entry();
        $entry2->setContentType($events);
        $entry2->setStatus(Entry::STATUS_PUBLISHED);
        $entry2->setAuthor($admin);
        $entry2->setUser($admin);
        $entry2->setActive(true);
        $entry2->setPublishedAt(new \DateTime());
        $this->addFieldValue($manager, $entry2, $fe1, 'Vernisage: Noves mirades');
        $this->addFieldValue($manager, $entry2, $fe2, '<p>Inauguració de la temporada amb una selecció d\'obres en format reduït.</p>');
        $this->addFieldValue($manager, $entry2, $fe3, '2026-07-01');
        $this->addFieldValue($manager, $entry2, $fe5, 'Girona');
        $manager->persist($entry2);

        $manager->flush();
    }

    /* Helper per crear un FieldValue i associar-lo a una Entry */
    private function addFieldValue(ObjectManager $manager, Entry $entry, FieldDefinition $fieldDef, ?string $value): void
    {
        $fv = new FieldValue();
        $fv->setFieldDefinition($fieldDef);
        $fv->setValue($value ?? '');
        $entry->addFieldValue($fv);
    }
}
