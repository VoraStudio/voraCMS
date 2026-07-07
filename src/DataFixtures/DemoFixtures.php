<?php

/* ===========================================================
   DemoFixtures — VoraCMS
   ===========================================================
   Crea entrades demo per als projectes existents.
   S'executa amb: php bin/console doctrine:fixtures:load

   Depèn d'AppFixtures (s'executa després).
   =========================================================== */

namespace App\DataFixtures;

use App\Entity\Entry;
use App\Entity\FieldValue;
use App\Entity\Project;
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

        $projects = $manager->getRepository(Project::class)->findBy(['user' => $admin]);
        if (empty($projects)) {
            return;
        }

        $project = $projects[0];

        $noticiesCt = null;
        $eventsCt = null;
        foreach ($project->getContentTypes() as $ct) {
            if ($ct->getSlug() === 'noticia') {
                $noticiesCt = $ct;
            } elseif ($ct->getSlug() === 'event') {
                $eventsCt = $ct;
            }
        }

        if ($noticiesCt === null && $eventsCt === null) {
            return;
        }

        /* Entrada demo: notícia */
        if ($noticiesCt !== null) {
            $entry = new Entry();
            $entry->setContentType($noticiesCt);
            $entry->setStatus(Entry::STATUS_PUBLISHED);
            $entry->setAuthor($admin);
            $entry->setUser($admin);
            $entry->setActive(true);
            $entry->setPublishedAt(new \DateTime());

            $fieldMap = [];
            foreach ($noticiesCt->getFields() as $fd) {
                $fieldMap[$fd->getSlug()] = $fd;
            }

            $values = [
                'titul' => 'Exposició d\'Art Contemporani 2026',
                'descripcio' => '<p>Gran exposició d\'art contemporani amb obres de diversos artistes internacionals.</p>',
                'data' => '2026-06-15',
                'location' => 'Girona',
                'imatge' => '',
            ];

            foreach ($values as $slug => $val) {
                if (isset($fieldMap[$slug])) {
                    $fv = new FieldValue();
                    $fv->setFieldDefinition($fieldMap[$slug]);
                    $fv->setValue($val);
                    $entry->addFieldValue($fv);
                }
            }

            $manager->persist($entry);
        }

        /* Entrada demo: event */
        if ($eventsCt !== null) {
            $entry2 = new Entry();
            $entry2->setContentType($eventsCt);
            $entry2->setStatus(Entry::STATUS_PUBLISHED);
            $entry2->setAuthor($admin);
            $entry2->setUser($admin);
            $entry2->setActive(true);
            $entry2->setPublishedAt(new \DateTime());

            $fieldMap2 = [];
            foreach ($eventsCt->getFields() as $fd) {
                $fieldMap2[$fd->getSlug()] = $fd;
            }

            $values2 = [
                'titul' => 'Vernisage: Noves mirades',
                'subtitol' => 'Inauguració de temporada',
                'descripcio' => '<p>Inauguració de la temporada amb una selecció d\'obres en format reduït.</p>',
                'data' => '2026-07-01',
                'location' => 'Girona',
            ];

            foreach ($values2 as $slug => $val) {
                if (isset($fieldMap2[$slug])) {
                    $fv = new FieldValue();
                    $fv->setFieldDefinition($fieldMap2[$slug]);
                    $fv->setValue($val);
                    $entry2->addFieldValue($fv);
                }
            }

            $manager->persist($entry2);
        }

        $manager->flush();
    }
}
