<?php

/* ===========================================================
   MediaController — Gestió de la mediateca amb tenant isolation.

   El MediaRepository té mètodes findByUser i
   findByUserOrdered que filtren per user_id.

   Els usuaris normals només veuen els seus fitxers. Els
   administradors veuen tots els fitxers.

   Pel mètode upload(), el MediaService ja assigna l'usuari
   actual internament.
   =========================================================== */

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Media;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Service\MediaService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/media')]
class MediaController extends AbstractController
{
    /* -----------------------------------------------------------
       index — Llista els fitxers multimèdia del client actual.
       Pel super-admin, mostra tots els fitxers (clientId null).
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_media_index')]
    public function index(MediaRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        if ($this->isGranted('ROLE_ADMIN')) {
            $media = $repo->findBy([], ['createdAt' => 'DESC']);
        } else {
            $user = $this->getUser();
            $media = $user instanceof User
                ? $repo->findByUserOrdered($user->getId())
                : [];
        }

        return $this->render('admin/media/index.html.twig', [
            'media' => $media,
        ]);
    }

    #[Route('/upload', name: 'admin_media_upload', methods: ['POST'])]
    public function upload(Request $request, MediaService $mediaService): JsonResponse
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file) {
            return $this->json(['error' => 'No s\'ha rebut cap fitxer.'], 400);
        }

        try {
            /* El MediaService ja assigna l'usuari actual
               internament durant el procés d'upload. */
            $media = $mediaService->upload($file, $this->getUser());
            return $this->json([
                'id' => $media->getId(),
                'url' => $media->getPath(),
                'filename' => $media->getOriginalFilename(),
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json(['error' => $e->getMessage()], 400);
        }
    }

    /* -----------------------------------------------------------
       delete — Elimina un fitxer multimèdia.
       Scoped al client actual: només es pot eliminar media
       del propi client.
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_media_delete', methods: ['POST'])]
    public function delete(Request $request, Media $media, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        /* Tenant isolation: només l'owner o ROLE_ADMIN poden esborrar */
        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof User || $media->getUser()?->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('No tens permís per eliminar aquesta imatge.');
            }
        }

        if ($this->isCsrfTokenValid('delete' . $media->getId(), $request->request->get('_token'))) {
            /* Eliminar el fitxer físic */
            $filePath = $this->getParameter('kernel.project_dir') . '/public' . $media->getPath();
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $em->remove($media);
            $em->flush();
            $this->addFlash('success', 'Imatge eliminada.');
        }

        return $this->redirectToRoute('admin_media_index');
    }

    /* -----------------------------------------------------------
       picker — Modal de selecció de fitxers multimèdia.
       Scoped al client actual igual que index().
       ----------------------------------------------------------- */
    #[Route('/picker', name: 'admin_media_picker')]
    public function picker(Request $request, MediaRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        if ($this->isGranted('ROLE_ADMIN')) {
            $media = $repo->findBy([], ['createdAt' => 'DESC']);
        } else {
            $user = $this->getUser();
            $media = $user instanceof User
                ? $repo->findByUserOrdered($user->getId())
                : [];
        }

        return $this->render('admin/media/picker.html.twig', [
            'media' => $media,
            'fieldId' => $request->query->get('field', ''),
            'multiple' => $request->query->get('multiple', 'false') === 'true',
        ]);
    }
}
