<?php

/* ===========================================================
   BaseContentController — Gestió de plantilles base de
   tipus de contingut (Notícies, Events, etc.).

   Aquestes plantilles es clonen automàticament quan es crea
   un projecte nou. Només accessible per ROLE_ADMIN.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Repository\ContentTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/base-content')]
class BaseContentController extends AbstractController
{
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
        new — Crea una nova plantilla base.
        ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_base_content_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $ct = new ContentType();
            $ct->setName($request->request->get('name'));
            $ct->setSlug($request->request->get('slug'));
            $ct->setDescription($request->request->get('description'));
            $ct->setBase(true);
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
            $this->addFlash('success', 'Plantilla base creada.');
            return $this->redirectToRoute('admin_base_content_index');
        }

        return $this->render('admin/base-content/new.html.twig', [
            'fieldTypes' => FieldDefinition::getTypes(),
        ]);
    }

    /* -----------------------------------------------------------
        edit — Edita una plantilla base (nom, descripció, camps).
        ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_base_content_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        ContentType $contentType,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$contentType->isBase() || $contentType->getProject() !== null) {
            throw $this->createNotFoundException('No és una plantilla base.');
        }

        if ($request->isMethod('POST')) {
            $contentType->setName($request->request->get('name'));
            $contentType->setDescription($request->request->get('description'));

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

            $em->flush();
            $this->addFlash('success', 'Plantilla actualitzada.');
            return $this->redirectToRoute('admin_base_content_index');
        }

        return $this->render('admin/base-content/edit.html.twig', [
            'template' => $contentType,
            'fieldTypes' => FieldDefinition::getTypes(),
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

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['á','é','í','ó','ú','à','è','ì','ò','ù','ñ'], ['a','e','i','o','u','a','e','i','o','u','n'], $text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        return trim($text, '_');
    }
}
