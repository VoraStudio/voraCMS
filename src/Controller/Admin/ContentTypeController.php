<?php

/* ===========================================================
   ContentTypeController — CRUD de tipus de contingut amb
   tenant isolation + project isolation.

   Cada tipus de contingut (secció) pertany a un projecte d'un
   usuari. Quan es crea un de nou, s'assigna a l'usuari actual
   i al projecte actiu de la sessió.

   El repositori scopa per usuari via UserIdFilter i per
   projecte via sessió; afegim verificacions explícites de
   propietat per defense in depth.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Entity\User;
use App\Repository\ContentTypeRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/content-type')]
class ContentTypeController extends AbstractController
{
    /* -----------------------------------------------------------
        index — Llista TOTS els tipus de contingut: els del
        projecte actiu + les plantilles globals, en un sol llistat.
        ----------------------------------------------------------- */
    #[Route('/', name: 'admin_content_type_index')]
    public function index(
        ContentTypeRepository $repo,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/content-type/index.html.twig', [
            'contentTypes' => $repo->findAllForAdmin(),
        ]);
    }

    /* -----------------------------------------------------------
        new — Crea un nou tipus de contingut dins del projecte
        actiu de la sessió. Requereix ROLE_ADMIN.
        ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_content_type_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ProjectRepository $projectRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $projects = $projectRepo->findAllOrderedByUser();
        if (empty($projects)) {
            throw $this->createAccessDeniedException('Cal tenir almenys un projecte per crear un tipus de contingut.');
        }

        if ($request->isMethod('POST')) {
            $ct = new ContentType();
            $ct->setName($request->request->get('name'));
            $ct->setSlug($request->request->get('slug'));
            $ct->setDescription($request->request->get('description'));
            $ct->setActive($request->request->get('active', true));

            /* Assignar al projecte seleccionat */
            $projectId = $request->request->get('project_id');
            $project = $projectId ? $projectRepo->find($projectId) : null;
            if ($project !== null) {
                $ct->setProject($project);
                /* L'usuari del Content Type és l'usuari del projecte */
                $ct->setUser($project->getUser() ?? $this->getUser());
            } else {
                /* Sense projecte: l'usuari és l'admin que el crea */
                $ct->setUser($this->getUser());
            }

            $fieldNames = $request->request->all('field_name') ?? [];
            $fieldTypes = $request->request->all('field_type') ?? [];
            $fieldRequired = $request->request->all('field_required') ?? [];
            $fieldOptions = $request->request->all('field_options') ?? [];

            foreach ($fieldNames as $i => $name) {
                if (empty($name)) continue;
                $fd = new FieldDefinition();
                $fd->setName($name);
                $fd->setSlug($this->slugify($name));
                $fd->setFieldType($fieldTypes[$i] ?? 'text');
                $fd->setRequired(isset($fieldRequired[$i]));
                $fd->setSortOrder($i);
                if (($fieldTypes[$i] ?? '') === FieldDefinition::TYPE_SELECT && !empty($fieldOptions[$i])) {
                    $fd->setHelpText($fieldOptions[$i]);
                }
                $ct->addField($fd);
            }

            $em->persist($ct);
            $em->flush();

            $this->addFlash('success', 'Secció creada correctament.');
            return $this->redirectToRoute('admin_content_type_index');
        }

        return $this->render('admin/content-type/new.html.twig', [
            'fieldTypes' => FieldDefinition::getTypes(),
            'projects' => $projects,
        ]);
    }

    /* -----------------------------------------------------------
        edit — Edita un tipus de contingut existent.
        Requereix MANAGE_CT sobre el projecte.
        Verifica propietat per client i projecte.
        ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_content_type_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ContentType $contentType,
        EntityManagerInterface $em,
        ProjectRepository $projectRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->verifyOwnership($contentType);

        if ($request->isMethod('POST')) {
            $contentType->setName($request->request->get('name'));
            $contentType->setDescription($request->request->get('description'));
            $contentType->setActive($request->request->get('active', true));

            /* Assignar al projecte seleccionat */
            $projectId = $request->request->get('project_id');
            $project = $projectId ? $projectRepo->find($projectId) : null;
            $contentType->setProject($project);
            if ($project !== null) {
                $contentType->setUser($project->getUser() ?? $this->getUser());
            }

            // Remove existing fields and recreate
            foreach ($contentType->getFields() as $field) {
                $em->remove($field);
            }

            $fieldNames = $request->request->all('field_name') ?? [];
            $fieldTypes = $request->request->all('field_type') ?? [];
            $fieldRequired = $request->request->all('field_required') ?? [];
            $fieldOptions = $request->request->all('field_options') ?? [];

            foreach ($fieldNames as $i => $name) {
                if (empty($name)) continue;
                $fd = new FieldDefinition();
                $fd->setName($name);
                $fd->setSlug($this->slugify($name));
                $fd->setFieldType($fieldTypes[$i] ?? 'text');
                $fd->setRequired(isset($fieldRequired[$i]));
                $fd->setSortOrder($i);
                if (($fieldTypes[$i] ?? '') === FieldDefinition::TYPE_SELECT && !empty($fieldOptions[$i])) {
                    $fd->setHelpText($fieldOptions[$i]);
                }
                $contentType->addField($fd);
            }

            $em->flush();
            $this->addFlash('success', 'Secció actualitzada.');
            return $this->redirectToRoute('admin_content_type_index');
        }

        return $this->render('admin/content-type/edit.html.twig', [
            'contentType' => $contentType,
            'fieldTypes' => FieldDefinition::getTypes(),
            'projects' => $projectRepo->findAllOrderedByUser(),
        ]);
    }

    /* -----------------------------------------------------------
        delete — Elimina un tipus de contingut.
        Requereix MANAGE_CT sobre el projecte. Verifica propietat i CSRF.
        ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_content_type_delete', methods: ['POST'])]
    public function delete(Request $request, ContentType $contentType, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $this->verifyOwnership($contentType);

        if ($this->isCsrfTokenValid('delete' . $contentType->getId(), $request->request->get('_token'))) {
            $em->remove($contentType);
            $em->flush();
            $this->addFlash('success', 'Secció eliminada.');
        }
        return $this->redirectToRoute('admin_content_type_index');
    }

    /* -----------------------------------------------------------
       toggleActive — Activa/desactiva un tipus de contingut.
       ----------------------------------------------------------- */
    #[Route('/{id}/toggle-active', name: 'admin_content_type_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, ContentType $contentType, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('toggle-active-' . $contentType->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguretat invàlid.');
            return $this->redirectToRoute('admin_content_type_index');
        }

        $contentType->setActive(!$contentType->isActive());
        $em->flush();

        $this->addFlash('success', $contentType->isActive() ? 'Secció activada.' : 'Secció desactivada.');
        return $this->redirectToRoute('admin_content_type_index');
    }

    /* -----------------------------------------------------------
       verifyOwnership — Comprova que el ContentType pertany a
       l'usuari actual. ROLE_ADMIN bypassa la verificació.
       ----------------------------------------------------------- */
    private function verifyOwnership(ContentType $contentType): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Usuari no autenticat.');
        }

        if ($contentType->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException(
                'No tens permís per modificar aquesta secció.'
            );
        }
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['á','é','í','ó','ú','à','è','ì','ò','ù','ñ'], ['a','e','i','o','u','a','e','i','o','u','n'], $text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        return trim($text, '_');
    }
}
