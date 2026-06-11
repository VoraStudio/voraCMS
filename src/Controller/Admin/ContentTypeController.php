<?php

/* ===========================================================
   ContentTypeController — CRUD de tipus de contingut amb
   tenant isolation (defense in depth).

   Tot i que el repositori ja scopa per client via
   ClientScope, afegim verificacions explícites de
   propietat als mètodes edit() i delete() per evitar
   que un client admin manipuli tipus d'un altre client
   si el filtre de Doctrine falla o es desactiva.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Repository\ContentTypeRepository;
use App\Service\ClientScope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/content-type')]
class ContentTypeController extends AbstractController
{
    public function __construct(
        private readonly ClientScope $clientScope,
    ) {}

    /* -----------------------------------------------------------
       index — Llista els tipus de contingut del client actual.
       El repositori ja aplica el filtre de tenant isolation
       automàticament via ClientScope.
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_content_type_index')]
    public function index(ContentTypeRepository $repo): Response
    {
        return $this->render('admin/content-type/index.html.twig', [
            'contentTypes' => $repo->findAll(),
            'currentClient' => $this->clientScope->getClient(),
        ]);
    }

    /* -----------------------------------------------------------
       new — Crea un nou tipus de contingut assignat al client
       actual. El client s'obté del ClientScope (null per a
       super-admin, que crea tipus globals sense client).
       ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_content_type_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $ct = new ContentType();
            $ct->setName($request->request->get('name'));
            $ct->setSlug($request->request->get('slug'));
            $ct->setDescription($request->request->get('description'));
            $ct->setActive($request->request->get('active', true));

            /* ── Assignar client actual (defense in depth) ── */
            $currentClient = $this->clientScope->getClient();
            if ($currentClient !== null) {
                $ct->setClient($currentClient);
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
                $ct->addField($fd);
            }

            $em->persist($ct);
            $em->flush();

            $this->addFlash('success', 'Tipus de contingut creat correctament.');
            return $this->redirectToRoute('admin_content_type_index');
        }

        return $this->render('admin/content-type/new.html.twig', [
            'fieldTypes' => FieldDefinition::getTypes(),
        ]);
    }

    /* -----------------------------------------------------------
       edit — Edita un tipus de contingut existent.
       Verifica que el tipus pertanyi al client actual
       (defense in depth, més enllà del filtre de Doctrine).
       ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_content_type_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContentType $contentType, EntityManagerInterface $em): Response
    {
        /* ── Verificació de propietat ── */
        $this->verifyOwnership($contentType);

        if ($request->isMethod('POST')) {
            $contentType->setName($request->request->get('name'));
            $contentType->setDescription($request->request->get('description'));
            $contentType->setActive($request->request->get('active', true));

            // Remove existing fields and recreate
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
            $this->addFlash('success', 'Tipus de contingut actualitzat.');
            return $this->redirectToRoute('admin_content_type_index');
        }

        return $this->render('admin/content-type/edit.html.twig', [
            'contentType' => $contentType,
            'fieldTypes' => FieldDefinition::getTypes(),
        ]);
    }

    /* -----------------------------------------------------------
       delete — Elimina un tipus de contingut.
       Verifica propietat i validació CSRF.
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_content_type_delete', methods: ['POST'])]
    public function delete(Request $request, ContentType $contentType, EntityManagerInterface $em): Response
    {
        /* ── Verificació de propietat ── */
        $this->verifyOwnership($contentType);

        if ($this->isCsrfTokenValid('delete' . $contentType->getId(), $request->request->get('_token'))) {
            $em->remove($contentType);
            $em->flush();
            $this->addFlash('success', 'Tipus de contingut eliminat.');
        }
        return $this->redirectToRoute('admin_content_type_index');
    }

    /* -----------------------------------------------------------
       verifyOwnership — Comprova que el ContentType pertany al
       client actual. Si no hi ha client (super-admin), permet
       l'accés. Si hi ha client però el ContentType no hi pertany,
       llança AccessDeniedException.
       ----------------------------------------------------------- */
    private function verifyOwnership(ContentType $contentType): void
    {
        $currentClient = $this->clientScope->getClient();

        /* Super-admin pot gestionar qualsevol tipus */
        if ($currentClient === null) {
            return;
        }

        /* Client admin només pot gestionar els seus tipus */
        if ($contentType->getClient()?->getId() !== $currentClient->getId()) {
            throw $this->createAccessDeniedException(
                'No tens permís per modificar aquest tipus de contingut.'
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
