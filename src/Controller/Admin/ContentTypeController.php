<?php

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Repository\ContentTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/content-type')]
class ContentTypeController extends AbstractController
{
    
    #[Route('/', name: 'admin_content_type_index')]
    public function index(ContentTypeRepository $repo): Response
    {
        return $this->render('admin/content-type/index.html.twig', [
            'contentTypes' => $repo->findAll(),
        ]);
    }

    #[Route('/new', name: 'admin_content_type_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $ct = new ContentType();
            $ct->setName($request->request->get('name'));
            $ct->setSlug($request->request->get('slug'));
            $ct->setDescription($request->request->get('description'));
            $ct->setActive($request->request->get('active', true));

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

    #[Route('/{id}/edit', name: 'admin_content_type_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, ContentType $contentType, EntityManagerInterface $em): Response
    {
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

    #[Route('/{id}/delete', name: 'admin_content_type_delete', methods: ['POST'])]
    public function delete(Request $request, ContentType $contentType, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $contentType->getId(), $request->request->get('_token'))) {
            $em->remove($contentType);
            $em->flush();
            $this->addFlash('success', 'Tipus de contingut eliminat.');
        }
        return $this->redirectToRoute('admin_content_type_index');
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(['á','é','í','ó','ú','à','è','ì','ò','ù','ñ'], ['a','e','i','o','u','a','e','i','o','u','n'], $text);
        $text = preg_replace('/[^a-z0-9]+/', '_', $text);
        return trim($text, '_');
    }
}
