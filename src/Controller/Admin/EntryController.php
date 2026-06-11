<?php

/* ===========================================================
   EntryController — CRUD d'entrades amb tenant isolation.

   Cada entrada pertany a un ContentType (que pertany a un
   Client) i a més té una referència directa client_id
   (defense in depth del disseny).

   Les verificacions de propietat als mètodes edit() i
   delete() comproven que l'entrada pertany al client
   actual, evitant accessos creuats entre tenants.

   En el mètode new(), assignem l'entrada al client actual
   obtingut via ClientScope per garantir l'aïllament.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\Entry;
use App\Entity\FieldValue;
use App\Entity\FieldDefinition;
use App\Repository\ContentTypeRepository;
use App\Repository\MediaRepository;
use App\Service\ClientScope;
use App\Service\MediaService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/entry')]
class EntryController extends AbstractController
{
    public function __construct(
        private readonly ClientScope $clientScope,
    ) {}

    /* -----------------------------------------------------------
       byType — Llista les entrades d'un tipus de contingut.
       Verifica que el ContentType pertany al client actual
       abans de mostrar les entrades.
       ----------------------------------------------------------- */
    #[Route('/type/{slug}', name: 'admin_entry_by_type')]
    public function byType(string $slug, ContentTypeRepository $ctRepo, MediaRepository $mediaRepo): Response
    {
        $contentType = $ctRepo->findBySlug($slug);
        if (!$contentType) throw $this->createNotFoundException();

        /* ── Verificació de propietat ── */
        $this->verifyContentTypeOwnership($contentType);

        $entries = $contentType->getEntries();
        $thumbnails = [];

        foreach ($entries as $entry) {
            $thumb = null;
            foreach ($entry->getFieldValues() as $fv) {
                $type = $fv->getFieldDefinition()?->getFieldType();
                $val = $fv->getValue();

                if ($type === FieldDefinition::TYPE_IMAGE && is_numeric($val)) {
                    $media = $mediaRepo->find((int) $val);
                    if ($media) { $thumb = $media->getPath(); break; }
                }

                if ($type === FieldDefinition::TYPE_GALLERY && $val) {
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
       new — Crea una nova entrada dins del ContentType
       especificat. Assigna el client actual a l'entrada
       (defense in depth) per garantir que sempre pertany
       al tenant correcte, independentment del ContentType.
       ----------------------------------------------------------- */
    #[Route('/new/{slug}', name: 'admin_entry_new', methods: ['GET', 'POST'])]
    public function new(Request $request, string $slug, ContentTypeRepository $ctRepo, EntityManagerInterface $em, MediaService $mediaService): Response
    {
        $contentType = $ctRepo->findBySlug($slug);
        if (!$contentType) throw $this->createNotFoundException();

        /* ── Verificació de propietat ── */
        $this->verifyContentTypeOwnership($contentType);

        if ($request->isMethod('POST')) {
            $entry = new Entry();
            $entry->setContentType($contentType);
            $entry->setStatus($request->request->get('status', Entry::STATUS_DRAFT));
            $entry->setLocale($request->request->get('locale', 'ca'));
            $entry->setAuthor($this->getUser());

            /* ── Assignar client actual a l'entrada ── */
            $currentClient = $this->clientScope->getClient();
            if ($currentClient !== null) {
                $entry->setClient($currentClient);
            }

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
            'locales' => ['ca' => 'Català', 'es' => 'Castellano', 'en' => 'English'],
        ]);
    }

    /* -----------------------------------------------------------
       edit — Edita una entrada existent.
       Verifica que l'entrada pertany al client actual abans
       de permetre la modificació.
       ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_entry_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entry $entry, EntityManagerInterface $em, MediaService $mediaService, MediaRepository $mediaRepo): Response
    {
        /* ── Verificació de propietat ── */
        $this->verifyEntryOwnership($entry);

        // Build map of media ID → path for previews
        $mediaPaths = [];
        foreach ($entry->getFieldValues() as $fv) {
            $type = $fv->getFieldDefinition()?->getFieldType();
            $val = $fv->getValue();

            if ($type === FieldDefinition::TYPE_IMAGE && is_numeric($val)) {
                $m = $mediaRepo->find((int) $val);
                if ($m) $mediaPaths[$val] = $m->getPath();
            }

            if ($type === FieldDefinition::TYPE_GALLERY && $val) {
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
            $entry->setLocale($request->request->get('locale', 'ca'));

            foreach ($entry->getContentType()->getFields() as $fieldDef) {
                $value = $this->resolveFieldValue($request, $fieldDef, $mediaService);
                foreach ($entry->getFieldValues() as $fv) {
                    if ($fv->getFieldDefinition()->getId() === $fieldDef->getId()) {
                        $fv->setValue($value ?? '');
                    }
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
            'locales' => ['ca' => 'Català', 'es' => 'Castellano', 'en' => 'English'],
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
       verifyEntryOwnership — Comprova que l'entrada pertany al
       client actual. Super-admin bypassa la verificació.
       ----------------------------------------------------------- */
    private function verifyEntryOwnership(Entry $entry): void
    {
        $currentClient = $this->clientScope->getClient();

        if ($currentClient === null) {
            return; /* Super-admin: accés complet */
        }

        if ($entry->getClient()?->getId() !== $currentClient->getId()) {
            throw $this->createAccessDeniedException(
                'No tens permís per modificar aquesta entrada.'
            );
        }
    }

    /* -----------------------------------------------------------
       verifyContentTypeOwnership — Comprova que el ContentType
       pertany al client actual.
       ----------------------------------------------------------- */
    private function verifyContentTypeOwnership(ContentType $contentType): void
    {
        $currentClient = $this->clientScope->getClient();

        if ($currentClient === null) {
            return; /* Super-admin: accés complet */
        }

        if ($contentType->getClient()?->getId() !== $currentClient->getId()) {
            throw $this->createAccessDeniedException(
                'No tens permís per accedir a aquest tipus de contingut.'
            );
        }
    }

    private function resolveFieldValue(Request $request, FieldDefinition $fieldDef, MediaService $mediaService): string
    {
        $fieldId = $fieldDef->getId();
        $raw = $request->request->get('field_' . $fieldId, '');

        // ── Image upload ──
        if ($fieldDef->getFieldType() === FieldDefinition::TYPE_IMAGE) {
            $file = $request->files->get('field_' . $fieldId . '_file');
            error_log('VORACMS_DEBUG: field=' . $fieldId . ' file=' . ($file ? 'present' : 'null') . ' raw=' . $raw);
            if ($file) {
                try {
                    error_log('VORACMS_DEBUG: calling upload with file=' . $file->getClientOriginalName() . ' size=' . $file->getSize());
                    $media = $mediaService->upload($file, $this->getUser());
                    error_log('VORACMS_DEBUG: upload OK, media_id=' . $media->getId());
                    return (string) $media->getId();
                } catch (\Throwable $e) {
                    error_log('VORACMS_DEBUG: upload ERROR: ' . $e->getMessage());
                    $this->addFlash('error', 'Error en pujar imatge: ' . $e->getMessage());
                }
            }
            return $raw;
        }

        // ── Gallery upload ──
        if ($fieldDef->getFieldType() === FieldDefinition::TYPE_GALLERY) {
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
}
