<?php

/* ===========================================================
   BaseContentController — Gestió de plantilles base de
   tipus de contingut (Notícies, Events, etc.).

   Aquestes plantilles es clonen automàticament quan es crea
   un projecte nou (si autoClone = true). A més, des del
   formulari de nova/editar es poden assignar a projectes
   existents de forma manual.

   Només accessible per ROLE_ADMIN.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Repository\ContentTypeRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/base-content')]
class BaseContentController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepo,
    ) {}

    /* -----------------------------------------------------------
        index — Llista totes les plantilles base.
        ----------------------------------------------------------- */
    #[Route('', name: 'admin_base_content_index')]
    public function index(ContentTypeRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/base-content/index.html.twig', [
            'templates' => $repo->findBaseTemplates(),
        ]);
    }

    /* -----------------------------------------------------------
        new — Crea una nova plantilla base i l'assigna a
        projectes seleccionats.
        ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_base_content_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ContentTypeRepository $ctRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $groupedProjects = $this->getGroupedProjects([]);

        if ($request->isMethod('POST')) {
            $ct = new ContentType();
            $ct->setName($request->request->get('name'));
            $ct->setSlug($request->request->get('slug'));
            $ct->setDescription($request->request->get('description'));
            $ct->setBase(true);
            $ct->setAutoClone((bool) $request->request->get('autoClone', false));
            $ct->setActive(true);
            $ct->setUser($this->getUser());

            $fieldNames = $request->request->all('field_name') ?? [];
            $fieldTypes = $request->request->all('field_type') ?? [];
            $fieldRequired = $request->request->all('field_required') ?? [];

            foreach ($fieldNames as $i => $name) {
                if (empty($name)) continue;
                $fd = new FieldDefinition();
                $fd->setName($name);
                $fd->setSlug($this->slugify($name));
                $fd->setFieldType($fieldTypes[$i] ?? 'text');
                $fd->setRequired(isset($fieldRequired[$i]));
                $fd->setSortOrder($i);
                $ct->addField($fd);
            }

            $em->persist($ct);
            $em->flush();

            /* Clonar als projectes seleccionats */
            $projectIds = $request->request->all('projects') ?? [];
            $this->cloneToProjects($ct, $projectIds, $em, $ctRepo);

            $this->addFlash('success', 'Plantilla base creada.');
            return $this->redirectToRoute('admin_base_content_index');
        }

        return $this->render('admin/base-content/new.html.twig', [
            'fieldTypes' => FieldDefinition::getTypes(),
            'groupedProjects' => $groupedProjects,
        ]);
    }

    /* -----------------------------------------------------------
        edit — Edita una plantilla base i permet clonar-la a
        projectes addicionals.
        ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_base_content_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ContentType $contentType,
        EntityManagerInterface $em,
        ContentTypeRepository $ctRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$contentType->isBase() || $contentType->getProject() !== null) {
            throw $this->createNotFoundException('No és una plantilla base.');
        }

        /* Projectes que ja tenen aquest content type */
        $existingProjectIds = $this->getExistingProjectIds($contentType, $ctRepo);
        $groupedProjects = $this->getGroupedProjects($existingProjectIds);

        if ($request->isMethod('POST')) {
            $contentType->setName($request->request->get('name'));
            $contentType->setDescription($request->request->get('description'));
            $contentType->setAutoClone((bool) $request->request->get('autoClone', false));

            foreach ($contentType->getFields() as $field) {
                $em->remove($field);
            }

            $fieldNames = $request->request->all('field_name') ?? [];
            $fieldTypes = $request->request->all('field_type') ?? [];
            $fieldRequired = $request->request->all('field_required') ?? [];

            foreach ($fieldNames as $i => $name) {
                if (empty($name)) continue;
                $fd = new FieldDefinition();
                $fd->setName($name);
                $fd->setSlug($this->slugify($name));
                $fd->setFieldType($fieldTypes[$i] ?? 'text');
                $fd->setRequired(isset($fieldRequired[$i]));
                $fd->setSortOrder($i);
                $contentType->addField($fd);
            }

            /* Clonar als projectes seleccionats (evitant duplicats) */
            $projectIds = $request->request->all('projects') ?? [];
            $this->cloneToProjects($contentType, $projectIds, $em, $ctRepo);

            $em->flush();
            $this->addFlash('success', 'Plantilla actualitzada.');
            return $this->redirectToRoute('admin_base_content_index');
        }

        return $this->render('admin/base-content/edit.html.twig', [
            'template' => $contentType,
            'fieldTypes' => FieldDefinition::getTypes(),
            'groupedProjects' => $groupedProjects,
        ]);
    }

    /* -----------------------------------------------------------
        show — Mostra una plantilla base en mode lectura (read-only).
        ----------------------------------------------------------- */
    #[Route('/{id}', name: 'admin_base_content_show', methods: ['GET'])]
    public function show(ContentType $contentType): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$contentType->isBase() || $contentType->getProject() !== null) {
            throw $this->createNotFoundException('No és una plantilla base.');
        }

        return $this->render('admin/base-content/show.html.twig', [
            'template' => $contentType,
        ]);
    }

    /* -----------------------------------------------------------
        delete — Elimina una plantilla base.
        ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_base_content_delete', methods: ['POST'])]
    public function delete(Request $request, ContentType $contentType, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$contentType->isBase() || $contentType->getProject() !== null) {
            throw $this->createNotFoundException('No és una plantilla base.');
        }

        if ($this->isCsrfTokenValid('delete' . $contentType->getId(), $request->request->get('_token'))) {
            $em->remove($contentType);
            $em->flush();
            $this->addFlash('success', 'Plantilla base eliminada correctament.');
        }

        return $this->redirectToRoute('admin_base_content_index');
    }

    /* -----------------------------------------------------------
        cloneToProjects — Clona una plantilla base als projectes
        indicats, evitant duplicats per slug+project.
        ----------------------------------------------------------- */
    private function cloneToProjects(
        ContentType $template,
        array $projectIds,
        EntityManagerInterface $em,
        ContentTypeRepository $ctRepo,
    ): void {
        foreach ($projectIds as $pid) {
            $project = $this->projectRepo->find((int) $pid);
            if (!$project) continue;

            /* Evitar duplicat: mateix slug + projecte */
            $existing = $ctRepo->findBySlug($template->getSlug(), $project->getId());
            if ($existing) continue;

            $ct = new ContentType();
            $ct->setName($template->getName());
            $ct->setSlug($template->getSlug());
            $ct->setDescription($template->getDescription());
            $ct->setActive(true);
            $ct->setBase(false);
            $ct->setAutoClone(false);
            $ct->setUser($project->getUser() ?? $this->getUser());
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

            $em->persist($ct);
        }

        $em->flush();
    }

    /* -----------------------------------------------------------
        getGroupedProjects — Retorna tots els projectes actius
        agrupats per usuari, marcant els que ja tenen aquest CT.
        ----------------------------------------------------------- */
    private function getGroupedProjects(array $existingProjectIds): array
    {
        $allProjects = $this->projectRepo->findAllOrderedByUser();
        $groups = [];

        foreach ($allProjects as $project) {
            $user = $project->getUser();
            $key = $user?->getId() ?? 0;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'user' => $user,
                    'projects' => [],
                ];
            }
            $groups[$key]['projects'][] = [
                'project' => $project,
                'exists' => in_array($project->getId(), $existingProjectIds, true),
            ];
        }

        return array_values($groups);
    }

    /* -----------------------------------------------------------
        getExistingProjectIds — Retorna IDs dels projectes que ja
        tenen un content type amb el mateix slug (per edit).
        ----------------------------------------------------------- */
    private function getExistingProjectIds(ContentType $template, ContentTypeRepository $ctRepo): array
    {
        $all = $ctRepo->findBy(['slug' => $template->getSlug(), 'base' => false]);
        return array_map(fn($ct) => $ct->getProject()?->getId(), array_filter($all, fn($ct) => $ct->getProject() !== null));
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['á','é','í','ó','ú','à','è','ì','ò','ù','ñ'], ['a','e','i','o','u','a','e','i','o','u','n'], $text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        return trim($text, '_');
    }
}
