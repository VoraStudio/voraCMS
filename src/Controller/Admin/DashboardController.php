<?php

namespace App\Controller\Admin;

use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(ContentTypeRepository $ctRepo, EntryRepository $entryRepo): Response
    {
        $contentTypes = $ctRepo->findActive();
        $stats = [];
        foreach ($contentTypes as $ct) {
            $stats[] = [
                'type' => $ct,
                'total' => count($ct->getEntries()),
                'published' => count($entryRepo->findPublishedByType($ct->getSlug())),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'contentTypes' => $contentTypes,
        ]);
    }
}
