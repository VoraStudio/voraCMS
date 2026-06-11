<?php

/* ===========================================================
   MediaController — Gestió de la mediateca amb tenant isolation.

   El MediaRepository ja té mètodes findByClient i
   findByClientOrdered que filtren per client_id.

   Aquí afegim ClientScope per resoldre el client actual
   i passar-lo al repositori, garantint que cada client
   només vegi els seus propis fitxers multimèdia.

   Pel mètode upload(), el MediaService ja assigna el
   client via ClientScope internament, així que no cal
   modificar-lo aquí.
   =========================================================== */

namespace App\Controller\Admin;

use App\Repository\MediaRepository;
use App\Service\ClientScope;
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
    public function __construct(
        private readonly ClientScope $clientScope,
    ) {}

    /* -----------------------------------------------------------
       index — Llista els fitxers multimèdia del client actual.
       Pel super-admin, mostra tots els fitxers (clientId null).
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_media_index')]
    public function index(MediaRepository $repo): Response
    {
        $clientId = $this->clientScope->getClientId();

        if ($clientId !== null) {
            $media = $repo->findByClientOrdered($clientId);
        } else {
            $media = $repo->findBy([], ['createdAt' => 'DESC']);
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
            /* El MediaService ja assigna el client via ClientScope
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
       picker — Modal de selecció de fitxers multimèdia.
       Scoped al client actual igual que index().
       ----------------------------------------------------------- */
    #[Route('/picker', name: 'admin_media_picker')]
    public function picker(Request $request, MediaRepository $repo): Response
    {
        $clientId = $this->clientScope->getClientId();

        if ($clientId !== null) {
            $media = $repo->findByClientOrdered($clientId);
        } else {
            $media = $repo->findBy([], ['createdAt' => 'DESC']);
        }

        return $this->render('admin/media/picker.html.twig', [
            'media' => $media,
            'fieldId' => $request->query->get('field', ''),
        ]);
    }
}
