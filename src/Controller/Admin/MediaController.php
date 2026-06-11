<?php

namespace App\Controller\Admin;

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
    #[Route('/', name: 'admin_media_index')]
    public function index(MediaRepository $repo): Response
    {
        return $this->render('admin/media/index.html.twig', [
            'media' => $repo->findBy([], ['createdAt' => 'DESC']),
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

    #[Route('/picker', name: 'admin_media_picker')]
    public function picker(Request $request, MediaRepository $repo): Response
    {
        return $this->render('admin/media/picker.html.twig', [
            'media' => $repo->findBy([], ['createdAt' => 'DESC']),
            'fieldId' => $request->query->get('field', ''),
        ]);
    }
}
