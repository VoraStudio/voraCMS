<?php

/* ══════════════════════════════════════════════════════════════
   voracms:seed-project — Crea el projecte contenidor per
   als clients existents i assigna els tipus de contingut
   actuals al projecte corresponent.

   Per què serveix:
   ---------------
   Amb el nou model multi-projecte, cada client pot tenir
   un o més "projectes" que agrupen els seus tipus de
   contingut (seccions). Aquest comando migra les dades
   existents al nou esquema.

   Què fa exactament:
   -----------------
   1. Per cada client amb ContentTypes (tipus de contingut),
      crea un projecte per defecte amb el nom del client.
   2. Assigna tots els ContentTypes existents d'aquest
      client al projecte acabat de crear.

   Exemple:
   --------
   Client "Default" → Projecte "VoraStudio"
     ├── ContentType: Notícies
     ├── ContentType: Events
     └── ContentType: Projectes

   Ús:
   ---
   php bin/console voracms:seed-project [--dry-run]

   --dry-run: mostra què faria sense executar-ho.
   ══════════════════════════════════════════════════════════════ */

namespace App\Command;

use App\Entity\ContentType;
use App\Entity\Project;
use App\Repository\ClientRepository;
use App\Repository\ContentTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'voracms:seed-project',
    description: 'Crea un projecte per defecte per cada client i hi assigna els seus ContentTypes',
)]
class VoracmsSeedProjectCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClientRepository $clientRepo,
        private readonly ContentTypeRepository $ctRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Mostra què faria sense persistir els canvis'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title('Seed: Projectes per defecte');

        /* ── Recuperem tots els clients actius ── */
        $clients = $this->clientRepo->findBy(['active' => true]);

        if (empty($clients)) {
            $io->warning('No hi ha clients actius.');
            return Command::SUCCESS;
        }

        $created = 0;
        $assigned = 0;

        foreach ($clients as $client) {
            /* Obtenim els ContentTypes del client que NO tinguin projecte assignat */
            $orphanCTs = $this->ctRepo->findBy([
                'client' => $client,
                'project' => null,
            ]);

            if (empty($orphanCTs)) {
                /* El client ja té tots els seus ContentTypes assignats a un projecte */
                continue;
            }

            /* Determinem el nom del projecte
               - Client "Default" → "VoraStudio" (és el vostre)
               - Altres clients    → el nom del client tal qual */
            if ($client->getSlug() === 'default') {
                $projectName = 'VoraStudio';
            } else {
                $projectName = $client->getName();
            }
            $projectSlug = $this->slugify($projectName);

            $created++;  /* Comptem abans de bifurcar per al --dry-run */
            $assigned += count($orphanCTs);

            if ($dryRun) {
                $io->section(sprintf('[DRY-RUN] Client: %s', $client->getName()));
                $io->writeln(sprintf('  → Crearà projecte: "%s" (slug: %s)', $projectName, $projectSlug));
                $io->writeln(sprintf('  → Assignarà %d ContentTypes:', count($orphanCTs)));
                foreach ($orphanCTs as $ct) {
                    $io->writeln(sprintf('    - %s (%s)', $ct->getName(), $ct->getSlug()));
                }
                continue;
            }

            /* Creem el projecte contenidor */
            $project = new Project();
            $project->setName($projectName);
            $project->setSlug($projectSlug);
            $project->setActive(true);
            $project->setClient($client);

            $this->em->persist($project);

            /* Assignem tots els ContentTypes sense projecte al projecte */
            foreach ($orphanCTs as $ct) {
                $ct->setProject($project);
            }

            $io->writeln(sprintf(
                '  ✓ Projecte "%s" creat amb %d ContentTypes assignats',
                $projectName,
                count($orphanCTs)
            ));
        }

        if (!$dryRun) {
            $this->em->flush();
            $io->success(sprintf(
                'Seed completat: %d projectes creats, %d ContentTypes assignats.',
                $created,
                $assigned
            ));
        } else {
            $io->success(sprintf(
                '[DRY-RUN] Es crearan %d projectes i s\'assignaran %d ContentTypes.',
                $created,
                $assigned
            ));
        }

        return Command::SUCCESS;
    }

    /* ─── Converteix un text en slug net ─── */
    /* Exemple: "VoraStudio" → "vorastudio", "Victoria Taylor" → "victoria-taylor" */
    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(
            ['á','é','í','ó','ú','à','è','ì','ò','ù','ñ','ü'],
            ['a','e','i','o','u','a','e','i','o','u','n','u'],
            $text
        );
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
