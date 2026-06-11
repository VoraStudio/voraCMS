<?php

/* ===========================================================
   DashboardController — Panell principal d'administració.
   Comportament segons el rol:

   - SUPER_ADMIN: veu tots els clients amb les seves
     estadístiques agregades. Pot seleccionar un client
     per veure'n el detall.
   - ROLE_USER (client admin): veu només les estadístiques
     del seu client, scoped via ClientScope.

   El repositori ContentTypeRepository ja aplica tenant
   isolation automàticament via ClientScope, així que
   findActive() retorna només els tipus del client actual.
   =========================================================== */

namespace App\Controller\Admin;

use App\Repository\ClientRepository;
use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use App\Service\ClientScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ClientScope $clientScope,
    ) {}

    /* -----------------------------------------------------------
       index — Renderitza el dashboard amb estadístiques scoped
       al client actual. Per super-admin, mostra una visió global
       amb tots els clients.
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        ClientRepository $clientRepo,
    ): Response {
        /* ── Resolem el client actual ── */
        $currentClient = $this->clientScope->getClient();
        $isSuperAdmin = $this->clientScope->isSuperAdmin();

        /* ── ContentTypes scoped al client actual ──
           El repositori ja aplica el filtre via ClientScope */
        $contentTypes = $ctRepo->findActive();

        $stats = [];
        foreach ($contentTypes as $ct) {
            $stats[] = [
                'type' => $ct,
                'total' => count($ct->getEntries()),
                'published' => count($entryRepo->findPublishedByType($ct->getSlug())),
            ];
        }

        /* ── Per super-admin: llista de tots els clients ── */
        $clients = [];
        if ($isSuperAdmin) {
            $clients = $clientRepo->findBy([], ['name' => 'ASC']);
        }

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'contentTypes' => $contentTypes,
            'clients' => $clients,
        ]);
    }
}
