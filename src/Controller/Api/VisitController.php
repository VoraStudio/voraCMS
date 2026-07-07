<?php

/* ===========================================================
   VisitController — Registre de visites des del frontend.
   POST /api/visit — Desa una visita amb usuari, entrada i IP.
   =========================================================== */

namespace App\Controller\Api;

use App\Entity\Entry;
use App\Entity\Visit;
use App\Repository\EntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/visit')]
class VisitController extends AbstractController
{
    /* -----------------------------------------------------------
       POST /api/visit — Registra una visita
       Body: { entry_id: number (opcional), path: string }
       ----------------------------------------------------------- */
    #[Route('', name: 'api_visit', methods: ['POST'])]
    public function record(Request $request, EntityManagerInterface $em, EntryRepository $entryRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['entry_id'])) {
            return $this->json(['error' => 'entry_id required'], 400);
        }

        $entry = $entryRepo->find($data['entry_id']);
        if (!$entry || !$entry->getUser()) {
            return $this->json(['error' => 'entry not found'], 404);
        }

        $visit = new Visit();
        $visit->setUser($entry->getUser());
        $visit->setEntry($entry);
        $visit->setPath($data['path'] ?? null);
        $visit->setIp($request->getClientIp());
        $visit->setUserAgent($request->headers->get('User-Agent'));

        $em->persist($visit);
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
