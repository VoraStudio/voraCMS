<?php

/* ===========================================================
   VisitController — Registre de visites des del frontend.
   POST /api/visit — Desa una visita amb usuari, entrada i IP.

   Si la IP d'origen és de confiança (SSR), accepta client_ip
   i user_agent del cos JSON per proxyar la IP real del visitant.
   =========================================================== */

namespace App\Controller\Api;

use App\Entity\Visit;
use App\Repository\EntryRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/visit')]
class VisitController extends AbstractController
{
    #[Route('', name: 'api_visit', methods: ['POST'])]
    public function record(Request $request, EntityManagerInterface $em, EntryRepository $entryRepo, UserRepository $userRepo): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['entry_id'])) {
            return $this->json(['error' => 'entry_id requerit'], 400);
        }

        $entry = $entryRepo->find($data['entry_id']);
        if (!$entry || !$entry->getUser()) {
            return $this->json(['error' => 'Entrada no trobada'], 404);
        }

        /* Determinar IP i User-Agent: confiar en el cos JSON si la IP origen és de confiança */
        $clientIp = $request->getClientIp();
        $userAgent = $request->headers->get('User-Agent');

        /** @var string[] $trustedIps */
        $trustedIps = $userRepo->findAllAllowedIps();

        if (in_array($clientIp, $trustedIps, true)) {
            $clientIp = $data['client_ip'] ?? $clientIp;
            $userAgent = $data['user_agent'] ?? $userAgent;
        }

        $visit = new Visit();
        $visit->setUser($entry->getUser());
        $visit->setEntry($entry);
        $visit->setPath($data['path'] ?? null);
        $visit->setIp($clientIp);
        $visit->setUserAgent($userAgent);

        $em->persist($visit);
        $em->flush();

        return $this->json(['ok' => true]);
    }
}
