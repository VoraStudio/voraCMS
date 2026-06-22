<?php

/* ═══════════════════════════════════════════════════════════════════════
   ImportProjectsCommand — VoraCMS
   ═══════════════════════════════════════════════════════════════════════
   Importa els 12 projectes de Vora Studio des de projects.json
   al Content Type "Projectes" del client Default.

   Usage:
     php bin/console voracms:import-projects

   Què fa:
     1. Crea el ContentType "Projectes" (slug: projecte) amb camps
     2. Llegeix ../VoraStudio/data/projects.json
     3. Copia imatges al directori d'uploads del CMS
     4. Crea entries publicades per cada projecte

   Segur per reexecutar: si el ContentType ja existeix, l'usa.
   ═══════════════════════════════════════════════════════════════════════ */

namespace App\Command;

use App\Entity\Client;
use App\Entity\ContentType;
use App\Entity\Entry;
use App\Entity\FieldDefinition;
use App\Entity\FieldValue;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Service\ClientScope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[AsCommand(
    name: 'voracms:import-projects',
    description: 'Importa els projectes de Vora Studio al CMS',
)]
class ImportProjectsCommand extends Command
{
    private const CLIENT_SLUG = 'default';
    private const CT_SLUG = 'projecte';
    private const CT_NAME = 'Projectes';

    /* Map: project_id => portada image filename */
    private const PORTADA_MAP = [
        'aurex'            => 'aurexFinestra.webp',
        'comercial-ross'   => 'para3.webp',
        'cfood'            => 'cfood.webp',
        'guardavan'        => 'Targetes.webp',
        'wiar'             => 'wiar.webp',
        'raymel'           => 'band.webp',
        'spica'            => 'web.webp',
        'palmitohouse'     => 'Mockup 2.webp',
        'innovafp'         => 'Mokcup.webp',
        'novagal'          => 'Targeta_.webp',
        'dtast'            => 'band.webp',
        'vitoria-teylor'   => 'web.webp',
    ];

    private ?Client $client = null;
    private ?ContentType $contentType = null;
    private ?User $author = null;
    private string $voraImgDir;
    private string $cmsUploadDir;
    private int $clientId;

    /* Cache: source_path => Media entity — evita duplicar fitxers */
    private array $mediaCache = [];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ClientScope $clientScope,
        private readonly ClientRepository $clientRepo,
        string $projectDir,
    ) {
        parent::__construct();

        /* El directori d'imatges de VoraStudio (germà de voracms) */
        $this->voraImgDir = realpath($projectDir . '/../VoraStudio/img');
        if ($this->voraImgDir === false) {
            $this->voraImgDir = $projectDir . '/../VoraStudio/img';
        }

        $this->cmsUploadDir = $projectDir . '/public/uploads';
    }

    protected function configure(): void {}

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Importació de Projectes Vora Studio → VoraCMS');

        /* ─── 1. Resoldre client Default ─── */
        $this->client = $this->clientRepo->findBySlug(self::CLIENT_SLUG);
        if (!$this->client) {
            $io->error('Client "default" no trobat. Has executat les fixtures?');
            return Command::FAILURE;
        }
        $this->clientId = $this->client->getId();
        $this->clientScope->setClient($this->client);

        /* ─── 2. Agafar l'admin com a autor ─── */
        $this->author = $this->em->getRepository(User::class)->findOneBy(['email' => 'admin@vorastudio.cat']);
        if (!$this->author) {
            $io->warning('Admin no trobat. Les entries no tindran autor.');
        }

        /* ─── 3. Crear/obtenir ContentType ─── */
        $this->contentType = $this->getOrCreateContentType($io);
        if (!$this->contentType) {
            return Command::FAILURE;
        }

        /* ─── 4. Llegir projects.json ─── */
        $projectsFile = dirname($this->voraImgDir) . '/data/projects.json';
        if (!is_file($projectsFile)) {
            $io->error("No es troba projects.json a: $projectsFile");
            return Command::FAILURE;
        }
        $projects = json_decode(file_get_contents($projectsFile), true);
        if (!$projects) {
            $io->error('Error decodificant projects.json');
            return Command::FAILURE;
        }

        /* ─── 5. Importar cada projecte ─── */
        $io->section('Important projectes...');
        $io->progressStart(count($projects));

        $imported = 0;
        $skipped = 0;

        foreach ($projects as $project) {
            $result = $this->importProject($project, $io);
            if ($result === true) {
                $imported++;
            } elseif ($result === null) {
                $skipped++;
            }
            $io->progressAdvance();
        }

        $io->progressFinish();
        $this->em->flush();

        /* ─── 6. Resum ─── */
        $io->success(sprintf(
            'Importació completada: %d projectes importats, %d omitits (ja existien)',
            $imported,
            $skipped
        ));

        $io->section('🔗 Endpoint API');
        $io->text(sprintf(
            '  GET /api/projecte?client=%s&locale=ca',
            $this->client->getSlug()
        ));

        return Command::SUCCESS;
    }

    /* ══════════════════════════════════════════════════════════════
        ContentType + FieldDefinitions
       ══════════════════════════════════════════════════════════════ */

    private function getOrCreateContentType(SymfonyStyle $io): ?ContentType
    {
        $existing = $this->em->getRepository(ContentType::class)->findOneBy([
            'slug' => self::CT_SLUG,
            'client' => $this->client,
        ]);

        if ($existing) {
            $io->note(sprintf('ContentType "%s" ja existeix — l\'usarem.', self::CT_NAME));
            return $existing;
        }

        $io->text('Creant ContentType "Projectes"...');

        $ct = new ContentType();
        $ct->setName(self::CT_NAME);
        $ct->setSlug(self::CT_SLUG);
        $ct->setBase(false);
        $ct->setActive(true);
        $ct->setClient($this->client);
        $ct->setDescription('Projectes del portfolio de Vora Studio');

        /* ─── Field Definitions ─── */
        $fields = [
            ['name' => 'Títol',              'slug' => 'titol',         'type' => FieldDefinition::TYPE_TEXT,     'required' => true,  'sortOrder' => 0],
            ['name' => 'Pack',                'slug' => 'pack',          'type' => FieldDefinition::TYPE_TEXT,     'required' => false, 'sortOrder' => 1],
            ['name' => 'Portada',             'slug' => 'portada',       'type' => FieldDefinition::TYPE_IMAGE,    'required' => false, 'sortOrder' => 2],
            ['name' => 'Logo client',         'slug' => 'logo_client',   'type' => FieldDefinition::TYPE_IMAGE,    'required' => false, 'sortOrder' => 3],
            ['name' => 'Website',             'slug' => 'website',       'type' => FieldDefinition::TYPE_URL,      'required' => false, 'sortOrder' => 4],
            ['name' => 'Descripció',          'slug' => 'descripcio',    'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => false, 'sortOrder' => 5],
            ['name' => 'Tags',                'slug' => 'tags',          'type' => FieldDefinition::TYPE_TEXT,     'required' => false, 'sortOrder' => 6],
            ['name' => 'El Repte',            'slug' => 'repte',         'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => false, 'sortOrder' => 7],
            ['name' => 'L\'Estratègia',       'slug' => 'estrategia',    'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => false, 'sortOrder' => 8],
            ['name' => 'El Resultat',         'slug' => 'resultat',      'type' => FieldDefinition::TYPE_RICHTEXT, 'required' => false, 'sortOrder' => 9],
            ['name' => 'Galeria',             'slug' => 'galeria',       'type' => FieldDefinition::TYPE_GALLERY,  'required' => false, 'sortOrder' => 10],
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

        $this->em->persist($ct);
        $this->em->flush();

        $io->success('ContentType "Projectes" creat correctament.');

        return $ct;
    }

    /* ══════════════════════════════════════════════════════════════
        Importar un projecte
       ══════════════════════════════════════════════════════════════ */

    /* Cache per saber si ja hi ha entries d'aquest content type */
    private ?bool $hasExistingEntries = null;

    private function importProject(array $project, SymfonyStyle $io): ?bool
    {
        $projectId = $project['id'];
        $hero = $project['hero'] ?? [];

        /* ─── Comprovar si ja existeixen entries ─── */
        if ($this->hasExistingEntries === null) {
            $count = $this->em->getRepository(Entry::class)->count([
                'contentType' => $this->contentType,
                'client' => $this->client,
            ]);
            $this->hasExistingEntries = $count > 0;
        }
        if ($this->hasExistingEntries) {
            return null; // skipped — ja importat en una execució anterior
        }

        /* ─── Resoldre portada ─── */
        $portadaSrc = self::PORTADA_MAP[$projectId] ?? null;
        $portadaMedia = $portadaSrc ? $this->importImage($portadaSrc) : null;

        /* ─── Resoldre logo ─── */
        $logoSrc = $this->resolveFilenameFromPath($hero['logo'] ?? '');
        $logoMedia = $logoSrc ? $this->importImage($logoSrc) : null;

        /* ─── Resoldre galeria ─── */
        $galleryMediaIds = [];
        foreach ($project['gallery'] ?? [] as $galleryPath) {
            $gallerySrc = $this->resolveFilenameFromPath($galleryPath);
            if ($gallerySrc) {
                $media = $this->importImage($gallerySrc);
                if ($media) {
                    $galleryMediaIds[] = $media->getId();
                }
            }
        }

        /* ─── Strategy blocks ─── */
        $strategy = $project['strategy'] ?? [];
        $repte = $this->findStrategyText($strategy, 'EL REPTE');
        $estrategia = $this->findStrategyText($strategy, "L'ESTRATÈGIA");
        $resultat = $this->findStrategyText($strategy, 'EL RESULTAT');

        /* ─── Crear Entry ─── */
        $entry = new Entry();
        $entry->setContentType($this->contentType);
        $entry->setClient($this->client);
        $entry->setStatus(Entry::STATUS_PUBLISHED);
        $entry->setLocale('ca');
        $entry->setAuthor($this->author);
        $entry->setPublishedAt(new \DateTime());

        /* ─── Field values ─── */
        $fieldDefs = [];
        foreach ($this->contentType->getFields() as $fd) {
            $fieldDefs[$fd->getSlug()] = $fd;
        }

        $fieldMap = [
            'titol'       => $project['name'] ?? '',
            'pack'        => $hero['pack'] ?? $this->detectPack($project['name'] ?? ''),
            'portada'     => $portadaMedia ? (string) $portadaMedia->getId() : null,
            'logo_client' => $logoMedia ? (string) $logoMedia->getId() : null,
            'website'     => $hero['website'] ?? '',
            'descripcio'  => $hero['description'] ?? '',
            'tags'        => isset($hero['tags']) ? implode(', ', $hero['tags']) : '',
            'repte'       => $repte,
            'estrategia'  => $estrategia,
            'resultat'    => $resultat,
            'galeria'     => !empty($galleryMediaIds) ? implode(',', $galleryMediaIds) : null,
        ];

        foreach ($fieldMap as $slug => $value) {
            if (!isset($fieldDefs[$slug])) continue;
            if ($value === null || $value === '') continue;

            $fv = new FieldValue();
            $fv->setFieldDefinition($fieldDefs[$slug]);
            $fv->setValue((string) $value);
            $entry->addFieldValue($fv);
        }

        $this->em->persist($entry);

        /* Flush parcial per tenir IDs de media frescos */
        $this->em->flush();

        return true;
    }

    /* ══════════════════════════════════════════════════════════════
        Gestió d'imatges
       ══════════════════════════════════════════════════════════════ */

    private function importImage(string $filename): ?Media
    {
        $sourcePath = $this->voraImgDir . DIRECTORY_SEPARATOR . $filename;

        if (!is_file($sourcePath)) {
            return null;
        }

        /* Cache: si ja hem importat aquest fitxer, retornem el Media existent */
        if (isset($this->mediaCache[$sourcePath])) {
            return $this->mediaCache[$sourcePath];
        }

        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'webp', 'avif', 'png'])) {
            return null;
        }

        /* Safe filename */
        $safeFilename = uniqid() . '_' . time() . '.' . $ext;

        /* Client upload dir */
        $clientUploadDir = $this->cmsUploadDir . '/' . $this->clientId;
        if (!is_dir($clientUploadDir)) {
            mkdir($clientUploadDir, 0775, true);
        }

        /* Copiar fitxer */
        $destPath = $clientUploadDir . '/' . $safeFilename;
        if (!copy($sourcePath, $destPath)) {
            return null;
        }

        /* Crear Media entity */
        $media = new Media();
        $media->setFilename($safeFilename);
        $media->setOriginalFilename($filename);
        $media->setExtension($ext);
        $media->setMimeType($this->guessMimeType($ext));
        $media->setPath('/uploads/' . $this->clientId . '/' . $safeFilename);
        $media->setFileSize(filesize($destPath));
        $media->setClient($this->client);
        $media->setUploadedBy($this->author);

        $this->em->persist($media);
        $this->em->flush();

        $this->mediaCache[$sourcePath] = $media;

        return $media;
    }

    private function guessMimeType(string $ext): string
    {
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'webp'        => 'image/webp',
            'avif'        => 'image/avif',
            'png'         => 'image/png',
            default       => 'application/octet-stream',
        };
    }

    /* ══════════════════════════════════════════════════════════════
        Helpers
       ══════════════════════════════════════════════════════════════ */

    /* Extreu nom de fitxer d'una ruta tipus "../img/foo.webp" */
    private function resolveFilenameFromPath(string $path): ?string
    {
        if (empty($path)) return null;
        $basename = basename($path);
        return $basename ?: null;
    }

    /* Troba el text d'un bloc d'estratègia pel seu label */
    private function findStrategyText(array $strategy, string $label): ?string
    {
        foreach ($strategy as $block) {
            if (($block['label'] ?? '') === $label) {
                return $block['text'] ?? null;
            }
        }
        return null;
    }

    /* Detecta el pack segons el nom del projecte (fallback) */
    private function detectPack(string $projectName): string
    {
        $master = ['Aurex', 'Novagal', 'Vitoria'];
        $integral = ['C-Food', 'Guardavan', 'Raymel'];

        foreach ($master as $name) {
            if (str_contains($projectName, $name)) return 'Pack Master';
        }
        foreach ($integral as $name) {
            if (str_contains($projectName, $name)) return 'Pack Integral';
        }
        return 'Pack Essencial';
    }
}
