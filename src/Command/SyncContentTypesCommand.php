<?php

namespace App\Command;

use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:sync-content-types')]
class SyncContentTypesCommand extends Command
{
    private const BASE_SLUGS = ['event', 'noticia'];
    private const ADMIN_EMAIL = 'admin@vora.es';

    public function __construct(
        private EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Sincronitzar Content Types amb plantilles base');

        $baseTemplates = $this->em->getRepository(ContentType::class)
            ->findBy(['base' => true, 'project' => null]);

        $projects = $this->em->getRepository(Project::class)
            ->findBy(['active' => true]);

        $admin = $this->em->getRepository(User::class)
            ->findOneBy(['email' => self::ADMIN_EMAIL]);

        if (!$admin) {
            $io->error('No s\'ha trobat l\'usuari admin');
            return Command::FAILURE;
        }

        // Corregir slugs ANTES de sincronizar para evitar duplicados
        $io->section('Corregir slugs antics (location → ubicacio, titul → titol)');
        $this->fixOldSlugs($io);

        foreach ($baseTemplates as $base) {
            $io->section(sprintf('Plantilla base: %s (%s)', $base->getName(), $base->getSlug()));

            foreach ($projects as $project) {
                $io->writeln(sprintf('  Projecte: %s', $project->getName()));

                // Buscar si ya existe en este proyecto
                $existing = $this->em->getRepository(ContentType::class)
                    ->findOneBy(['slug' => $base->getSlug(), 'project' => $project]);

                if ($existing) {
                    $io->writeln(sprintf('    ✓ Ja existe (ID %d) — sincronitzant camps...', $existing->getId()));
                    $this->syncFields($base, $existing, $io);
                } else {
                    $io->writeln('    ✗ No existeix — creant...');
                    $this->createFromBase($base, $project, $admin, $io);
                }
            }
        }

        $this->em->flush();
        $io->success('Sincronització completada!');

        return Command::SUCCESS;
    }

    private function createFromBase(ContentType $base, Project $project, User $admin, SymfonyStyle $io): void
    {
        $owner = $project->getUser() ?? $admin;

        $ct = new ContentType();
        $ct->setName($base->getName());
        $ct->setSlug($base->getSlug());
        $ct->setDescription($base->getDescription());
        $ct->setActive(true);
        $ct->setBase(false);
        $ct->setAutoClone(false);
        $ct->setUser($owner);
        $ct->setProject($project);

        foreach ($base->getFields() as $baseField) {
            $field = new FieldDefinition();
            $field->setName($baseField->getName());
            $field->setSlug($baseField->getSlug());
            $field->setFieldType($baseField->getFieldType());
            $field->setRequired($baseField->isRequired());
            $field->setTranslatable($baseField->isTranslatable());
            $field->setHelpText($baseField->getHelpText());
            $field->setSortOrder($baseField->getSortOrder());
            $ct->addField($field);
            $this->em->persist($field);
        }

        $this->em->persist($ct);
        $io->writeln(sprintf('    → Creat ID %d amb %d camps', $ct->getId(), $base->getFields()->count()));
    }

    private function syncFields(ContentType $base, ContentType $target, SymfonyStyle $io): void
    {
        $targetFields = [];
        foreach ($target->getFields() as $f) {
            $targetFields[$f->getSlug()] = $f;
        }

        $added = 0;
        $sortOrder = 0;

        foreach ($base->getFields() as $baseField) {
            $slug = $baseField->getSlug();

            if (isset($targetFields[$slug])) {
                // Actualizar propiedades del campo existente
                $tf = $targetFields[$slug];
                $tf->setName($baseField->getName());
                $tf->setFieldType($baseField->getFieldType());
                $tf->setRequired($baseField->isRequired());
                $tf->setTranslatable($baseField->isTranslatable());
                $tf->setHelpText($baseField->getHelpText());
                $tf->setSortOrder($sortOrder);
            } else {
                // Campo nuevo: crear
                $field = new FieldDefinition();
                $field->setName($baseField->getName());
                $field->setSlug($slug);
                $field->setFieldType($baseField->getFieldType());
                $field->setRequired($baseField->isRequired());
                $field->setTranslatable($baseField->isTranslatable());
                $field->setHelpText($baseField->getHelpText());
                $field->setSortOrder($sortOrder);
                $target->addField($field);
                $this->em->persist($field);
                $added++;
            }
            $sortOrder++;
        }

        if ($added > 0) {
            $io->writeln(sprintf('    → Afegits %d camps nous', $added));
        } else {
            $io->writeln('    → Cap canvi necessari');
        }
    }

    private function fixOldSlugs(SymfonyStyle $io): void
    {
        $slugFixes = [
            'titul' => 'titol',
            'location' => 'ubicacio',
        ];

        $repo = $this->em->getRepository(FieldDefinition::class);

        foreach ($slugFixes as $oldSlug => $newSlug) {
            $fields = $repo->findBy(['slug' => $oldSlug]);
            foreach ($fields as $field) {
                $ct = $field->getContentType();
                if ($ct && !$ct->isBase()) {
                    $io->writeln(sprintf('    Renombrant slug "%s" → "%s" (CT: %s)', $oldSlug, $newSlug, $ct->getName()));
                    $field->setSlug($newSlug);
                }
            }
        }
    }
}
