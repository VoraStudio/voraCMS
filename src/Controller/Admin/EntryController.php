<?php

/* ===========================================================
   EntryController — CRUD d'entrades amb tenant isolation.

   Cada entrada pertany a un ContentType (que pertany a un
   usuari) i a més té una referència directa user_id
   (defense in depth del disseny).

   Les verificacions de propietat als mètodes edit() i
   delete() comproven que l'entrada pertany a l'usuari
   actual, evitant accessos creuats entre tenants.

   En el mètode new(), assignem l'entrada a l'usuari actual
   obtingut via el context de seguretat.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\Entry;
use App\Entity\FieldDefinition;
use App\Entity\FieldValue;
use App\Entity\User;
use App\Repository\ContentTypeRepository;
use App\Repository\MediaRepository;
use App\Repository\ProjectRepository;
use App\Service\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/entry')]
class EntryController extends AbstractController
{
    /* -----------------------------------------------------------
       byType — Llista les entrades d'un tipus de contingut.
       Verifica que el ContentType pertany a l'usuari actual
       abans de mostrar les entrades.
       ----------------------------------------------------------- */
    #[Route('/type/{slug}', name: 'admin_entry_by_type')]
    public function byType(
        string $slug,
        ContentTypeRepository $ctRepo,
        MediaRepository $mediaRepo,
        ProjectRepository $projectRepo,
        Request $request,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        /* Assegurem que hi ha un projecte actiu a sessió */
        $projectId = $this->ensureActiveProject($request, $projectRepo);
        if ($projectId === null) {
            return $this->redirectToRoute('admin_projects');
        }
        /* Filtrem el ContentType pel projecte actiu */
        $contentType = $ctRepo->findBySlug($slug, $projectId);
        if (!$contentType) throw $this->createNotFoundException();

        /* ── Verificació de propietat ── */
        $this->verifyContentTypeOwnership($contentType);
        $this->verifyContentTypeProject($contentType, $projectId);

        $entries = $contentType->getEntries();
        $thumbnails = [];

        foreach ($entries as $entry) {
            $thumb = null;
            foreach ($entry->getFieldValues() as $fv) {
                $type = $fv->getFieldDefinition()?->getFieldType();
                $val = $fv->getValue();

                if (($type === FieldDefinition::TYPE_IMAGE || $type === FieldDefinition::TYPE_GALLERY) && $val) {
                    $ids = array_filter(explode(',', $val));
                    if (!empty($ids) && is_numeric($ids[0])) {
                        $media = $mediaRepo->find((int) $ids[0]);
                        if ($media) { $thumb = $media->getPath(); break; }
                    }
                }
            }
            $thumbnails[$entry->getId()] = $thumb;
        }

        return $this->render('admin/entry/index.html.twig', [
            'contentType' => $contentType,
            'entries' => $entries,
            'thumbnails' => $thumbnails,
        ]);
    }

    /* -----------------------------------------------------------
       show — Mostra una entrada en mode lectura (read-only).
       ----------------------------------------------------------- */
    #[Route('/{id}', name: 'admin_entry_show', methods: ['GET'])]
    public function show(Entry $entry, MediaRepository $mediaRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        /* ── Verificació de propietat ── */
        $this->verifyEntryOwnership($entry);

        $mediaPaths = [];
        foreach ($entry->getFieldValues() as $fv) {
            $type = $fv->getFieldDefinition()?->getFieldType();
            $val = $fv->getValue();

            if (($type === FieldDefinition::TYPE_IMAGE || $type === FieldDefinition::TYPE_GALLERY) && $val) {
                foreach (array_filter(explode(',', $val)) as $id) {
                    if (is_numeric($id) && !isset($mediaPaths[$id])) {
                        $m = $mediaRepo->find((int) $id);
                        if ($m) $mediaPaths[$id] = $m->getPath();
                    }
                }
            }
        }

        return $this->render('admin/entry/show.html.twig', [
            'entry' => $entry,
            'contentType' => $entry->getContentType(),
            'mediaPaths' => $mediaPaths,
        ]);
    }

    /* -----------------------------------------------------------
       new — Crea una nova entrada dins del ContentType
       especificat. Assigna l'usuari actual a l'entrada
       (defense in depth) per garantir que sempre pertany
       al tenant correcte, independentment del ContentType.
       ----------------------------------------------------------- */
    #[Route('/new/{slug}', name: 'admin_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $slug, ContentTypeRepository $ctRepo, EntityManagerInterface $em, MediaService $mediaService): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        $projectId = $request->getSession()->get('_project_id');
        $contentType = $projectId !== null ? $ctRepo->findBySlug($slug, (int) $projectId) : $ctRepo->findBySlug($slug);
        if (!$contentType) throw $this->createNotFoundException();

        /* ── Verificació de propietat ── */
        $this->verifyContentTypeOwnership($contentType);

        if ($request->isMethod('POST')) {
            $entry = new Entry();
            $entry->setContentType($contentType);
            $entry->setStatus($request->request->get('status', Entry::STATUS_DRAFT));
            $entry->setAuthor($this->getUser());

            /* ── Assignar usuari actual a l'entrada ── */
            $entry->setUser($this->getUser());

            foreach ($contentType->getFields() as $fieldDef) {
                $value = $this->resolveFieldValue($request, $fieldDef, $mediaService);
                $fv = new FieldValue();
                $fv->setFieldDefinition($fieldDef);
                $fv->setValue($value ?? '');
                $entry->addFieldValue($fv);
            }

            if ($entry->getStatus() === Entry::STATUS_PUBLISHED) {
                $entry->setPublishedAt(new \DateTime());
            }

            $em->persist($entry);
            $em->flush();

            $this->addFlash('success', 'Entrada creada correctament.');
            return $this->redirectToRoute('admin_entry_by_type', ['slug' => $slug]);
        }

        return $this->render('admin/entry/new.html.twig', [
            'contentType' => $contentType,
        ]);
    }

    /* -----------------------------------------------------------
       edit — Edita una entrada existent.
       Verifica que l'entrada pertany a l'usuari actual abans
       de permetre la modificació.
       ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entry $entry, EntityManagerInterface $em, MediaService $mediaService, MediaRepository $mediaRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        /* ── Verificació de propietat ── */
        $this->verifyEntryOwnership($entry);

        // Build map of media ID → path for previews
        $mediaPaths = [];
        foreach ($entry->getFieldValues() as $fv) {
            $type = $fv->getFieldDefinition()?->getFieldType();
            $val = $fv->getValue();

            if (($type === FieldDefinition::TYPE_IMAGE || $type === FieldDefinition::TYPE_GALLERY) && $val) {
                foreach (array_filter(explode(',', $val)) as $id) {
                    if (is_numeric($id) && !isset($mediaPaths[$id])) {
                        $m = $mediaRepo->find((int) $id);
                        if ($m) $mediaPaths[$id] = $m->getPath();
                    }
                }
            }
        }

        if ($request->isMethod('POST')) {
            $entry->setStatus($request->request->get('status', Entry::STATUS_DRAFT));

            foreach ($entry->getContentType()->getFields() as $fieldDef) {
                $value = $this->resolveFieldValue($request, $fieldDef, $mediaService);
                $found = false;
                foreach ($entry->getFieldValues() as $fv) {
                    if ($fv->getFieldDefinition()->getId() === $fieldDef->getId()) {
                        $fv->setValue($value ?? '');
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $newFv = new FieldValue();
                    $newFv->setFieldDefinition($fieldDef);
                    $newFv->setValue($value ?? '');
                    $entry->addFieldValue($newFv);
                    $em->persist($newFv);
                }
            }

            if ($entry->getStatus() === Entry::STATUS_PUBLISHED && !$entry->getPublishedAt()) {
                $entry->setPublishedAt(new \DateTime());
            }

            $em->flush();
            $this->addFlash('success', 'Entrada actualitzada.');
            return $this->redirectToRoute('admin_entry_by_type', ['slug' => $entry->getContentType()->getSlug()]);
        }

        return $this->render('admin/entry/edit.html.twig', [
            'entry' => $entry,
            'contentType' => $entry->getContentType(),
            'mediaPaths' => $mediaPaths,
        ]);
    }

    /* -----------------------------------------------------------
       delete — Elimina una entrada.
       Verifica propietat abans d'eliminar i valida CSRF.
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_entry_delete', methods: ['POST'])]
    public function delete(Request $request, Entry $entry, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        /* ── Verificació de propietat ── */
        $this->verifyEntryOwnership($entry);

        $slug = $entry->getContentType()->getSlug();
        if ($this->isCsrfTokenValid('delete' . $entry->getId(), $request->request->get('_token'))) {
            $em->remove($entry);
            $em->flush();
            $this->addFlash('success', 'Entrada eliminada.');
        }
        return $this->redirectToRoute('admin_entry_by_type', ['slug' => $slug]);
    }

    /* -----------------------------------------------------------
       toggleActive — Activa/desactiva una entrada.
       ----------------------------------------------------------- */
    #[Route('/{id}/toggle-active', name: 'admin_entry_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, Entry $entry, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');
        $this->verifyEntryOwnership($entry);

        if (!$this->isCsrfTokenValid('toggle-active-' . $entry->getId(), $request->request->get('_token'))) {
            if ($request->isXmlHttpRequest()) {
                return $this->json(['error' => 'Token de seguretat invàlid.'], 400);
            }
            $this->addFlash('error', 'Token de seguretat invàlid.');
            return $this->redirectToRoute('admin_entry_by_type', ['slug' => $entry->getContentType()->getSlug()]);
        }

        $entry->setActive(!$entry->isActive());
        $em->flush();

        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'active' => $entry->isActive(),
                'message' => $entry->isActive() ? 'Entrada activada.' : 'Entrada desactivada.',
            ]);
        }

        $this->addFlash('success', $entry->isActive() ? 'Entrada activada.' : 'Entrada desactivada.');
        return $this->redirectToRoute('admin_entry_by_type', ['slug' => $entry->getContentType()->getSlug()]);
    }

    /* -----------------------------------------------------------
       verifyEntryOwnership — Comprova que l'entrada pertany a
       l'usuari actual. ROLE_ADMIN bypassa la verificació.
       ----------------------------------------------------------- */
    private function verifyEntryOwnership(Entry $entry): void
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return;
        }

        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Usuari no autenticat.');
        }

        if ($entry->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException(
                'No tens permís per modificar aquesta entrada.'
            );
        }
    }

    /* -----------------------------------------------------------
       verifyContentTypeOwnership — Comprova que el ContentType
       pertany a l'usuari actual.
       ----------------------------------------------------------- */
    private function verifyContentTypeOwnership(ContentType $contentType): void
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
                'No tens permís per accedir a aquest tipus de contingut.'
            );
        }
    }

    /* -----------------------------------------------------------
       verifyContentTypeProject — Comprova que el ContentType
       pertany al projecte actiu o és un tipus base.
       ----------------------------------------------------------- */
    private function verifyContentTypeProject(ContentType $contentType, int $projectId): void
    {
        $ctProject = $contentType->getProject();
        if ($ctProject !== null && $ctProject->getId() !== $projectId) {
            throw $this->createAccessDeniedException(
                'Aquest tipus de contingut no pertany al projecte actiu.'
            );
        }
    }

    private function resolveFieldValue(Request $request, FieldDefinition $fieldDef, MediaService $mediaService): string
    {
        $fieldId = $fieldDef->getId();
        $raw = $request->request->get('field_' . $fieldId, '');

        // ── Image / Gallery upload (multi-image, comma-separated) ──
        if ($fieldDef->getFieldType() === FieldDefinition::TYPE_IMAGE ||
            $fieldDef->getFieldType() === FieldDefinition::TYPE_GALLERY) {
            $files = $request->files->all('field_' . $fieldId . '_files') ?? [];
            $existingIds = $raw ? array_filter(explode(',', $raw)) : [];

            $allIds = [];
            foreach ($existingIds as $id) {
                if ($id !== '__upload__') {
                    $allIds[] = $id;
                }
            }

            foreach ($files as $file) {
                if ($file) {
                    try {
                        $media = $mediaService->upload($file, $this->getUser());
                        $allIds[] = (string) $media->getId();
                    } catch (\Throwable $e) {
                        $this->addFlash('error', 'Error en pujar imatge: ' . $e->getMessage());
                    }
                }
            }

            return implode(',', $allIds);
        }

        // ── YouTube: extract video ID from URL ──
        if ($fieldDef->getFieldType() === FieldDefinition::TYPE_YOUTUBE && $raw) {
            preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $raw, $m);
            return $m[1] ?? $raw;
        }

        return $raw;
    }

    /* -----------------------------------------------------------
        ensureActiveProject — Verifica que hi hagi un projecte
        actiu a sessió.
          - Valida que el projecte a sessió sigui del user actual
          - Si no n'hi ha, auto-selecciona (scoped a l'usuari)
          - 0 projectes → redirect a crear-ne un
          - 1 projecte  → l'auto-selecciona
          - 2+ projectes → redirect a llista per triar
        Retorna el project_id o null (si cal redirect).
        ----------------------------------------------------------- */
    private function ensureActiveProject(Request $request, ?ProjectRepository $projectRepo = null): ?int
    {
        $session = $request->getSession();
        $projectId = $session->get('_project_id');
        $user = $this->getUser();

        /* Si hi ha projecte a sessió, validar que pertany a l'usuari */
        if ($projectId !== null && $projectRepo !== null && !$this->isGranted('ROLE_ADMIN') && $user instanceof User) {
            $project = $projectRepo->find($projectId);
            if (!$project || $project->getUser()?->getId() !== $user->getId()) {
                $session->remove('_project_id');
                $projectId = null;
            }
        }

        if ($projectId !== null) {
            return (int) $projectId;
        }

        /* No hi ha projecte — auto-seleccionar scoped a l'usuari */
        if ($projectRepo === null) {
            return null;
        }

        if (!$this->isGranted('ROLE_ADMIN') && $user instanceof User) {
            $projects = $projectRepo->findBy(['user' => $user, 'active' => true], ['id' => 'DESC']);
        } else {
            $projects = $projectRepo->findActive();
        }

        $count = count($projects);

        if ($count === 0) {
            return null;
        }

        if ($count === 1) {
            $session->set('_project_id', $projects[0]->getId());
            return $projects[0]->getId();
        }

        return null; /* 2+ projectes — redirect a llista */
    }
}
