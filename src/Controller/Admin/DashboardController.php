<?php

/* ===========================================================
   DashboardController — Panell principal d'administració.
   Comportament segons el rol:

   - ROLE_ADMIN: panell global amb tots els clients, últims
     projectes, últimes publicacions i mètriques agregades.
   - ROLE_MOD / ROLE_USUARIO: veu només el seu client i el
     seu projecte actiu amb les seccions i mètriques.

   Admin path:
     metrics → [ Clients, Proyectos, Publicaciones ]
     → Últims projectes (amb nom del client)
     → Últimes publicacions (amb client/tipus)
     → Fitxes de clients amb mètriques per client
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\Entry;
use App\Repository\ClientRepository;
use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use App\Repository\MediaRepository;
use App\Repository\ProjectRepository;
use App\Repository\VisitRepository;
use App\Service\ClientScope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    public function __construct(
        private readonly ClientScope $clientScope,
    ) {}

    /* -----------------------------------------------------------
       index
       ───────────────────────────────────────────────────────────
       ADMIN  → panell global amb clients, projectes, publicacions
       MOD/USUARIO → panell scoped al seu client/projecte actiu
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        Request $request,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        ClientRepository $clientRepo,
        ProjectRepository $projectRepo,
        MediaRepository $mediaRepo,
        EntityManagerInterface $em,
        VisitRepository $visitRepo,
    ): Response {
        $currentClient = $this->clientScope->getClient();
        $isAdmin = $this->clientScope->isAdmin();

        /* ═══════════════════════════════════════════════════════
           ADMIN DASHBOARD — Global, tots els clients
           ═══════════════════════════════════════════════════════ */
        if ($isAdmin) {
            $clients = $clientRepo->findBy([], ['name' => 'ASC']);

            /* ── Últims projectes (amb client) ── */
            $latestProjects = $projectRepo->findLatestActive(6);

            /* ── Últimes publicacions (amb client) ── */
            $latestPublications = $entryRepo->findLatestPublished(10);

            /* ── Total publicacions globals ── */
            $totalPublishedGlobally = (int) $em->createQueryBuilder()
                ->select('COUNT(e.id)')
                ->from(Entry::class, 'e')
                ->where('e.status = :status')
                ->setParameter('status', Entry::STATUS_PUBLISHED)
                ->getQuery()
                ->getSingleScalarResult();

            /* ── Visites d'avui ── */
            $totalVisitsToday = $visitRepo->countTodayGlobal();

            /* ── Mètriques per client ── */
            $clientMetrics = [];
            foreach ($clients as $client) {
                $clientMetrics[] = [
                    'client' => $client,
                    'projects' => count($client->getProjects()),
                    'published' => $entryRepo->countPublishedByClient($client->getId()),
                    'today' => $entryRepo->countTodayByClient($client->getId()),
                    'visitsToday' => $visitRepo->countTodayByClient($client->getId()),
                ];
            }

            return $this->render('admin/dashboard.html.twig', [
                'isAdmin' => true,
                'latestProjects' => $latestProjects,
                'latestPublications' => $latestPublications,
                'clientMetrics' => $clientMetrics,
                'metrics' => [
                    'totalClientes' => count($clients),
                    'totalProyectos' => $projectRepo->count([]),
                    'totalPublicaciones' => $totalPublishedGlobally,
                    'totalVisites' => $totalVisitsToday,
                ],
                'currentClient' => $currentClient,
            ]);
        }

        /* ═══════════════════════════════════════════════════════
           MOD / USUARIO DASHBOARD — Scoped al seu client
           ═══════════════════════════════════════════════════════ */
        $session = $request->getSession();
        $activeProjectId = $session->get('_project_id');

        $projects = $projectRepo->findActive();
        $totalProjects = count($projects);

        if ($totalProjects === 0) {
            return $this->redirectToRoute('admin_project_new');
        }

        /* ── ContentTypes i mètriques ── */
        $stats = [];
        $totalEntries = 0;
        $totalPublished = 0;
        $projectToShow = null;

        if ($totalProjects === 1) {
            $pid = $projects[0]->getId();
            $session->set('_project_id', $pid);
            $projectToShow = $projects[0];
        } elseif ($activeProjectId !== null) {
            $projectToShow = $projectRepo->find($activeProjectId);
        } else {
            $pid = $projects[0]->getId();
            $session->set('_project_id', $pid);
            $projectToShow = $projects[0];
        }

        if ($projectToShow !== null) {
            $contentTypes = $ctRepo->findActive($projectToShow->getId());
            foreach ($contentTypes as $ct) {
                $stats[] = [
                    'type' => $ct,
                    'total' => count($ct->getEntries()),
                    'published' => count($entryRepo->findPublishedByType($ct->getSlug())),
                ];
                $totalEntries += end($stats)['total'];
                $totalPublished += end($stats)['published'];
            }
        }

        $clientId = $this->clientScope->getClientId();
        $totalMedia = $clientId !== null
            ? count($mediaRepo->findByClient($clientId))
            : 0;

        return $this->render('admin/dashboard.html.twig', [
            'stats' => $stats,
            'projects' => $projects,
            'currentClient' => $currentClient,
            'projectToShow' => $projectToShow,
            'activeProject' => $projectToShow,
            'metrics' => [
                'totalEntries' => $totalEntries,
                'totalPublished' => $totalPublished,
                'totalMedia' => $totalMedia,
                'totalProjects' => max($totalProjects, 1),
            ],
        ]);
    }

    /* -----------------------------------------------------------
       switchProject — Canvia el projecte actiu a sessió.
       GET /admin/switch-project/{id} → redirect al dashboard
       ----------------------------------------------------------- */
    #[Route('/switch-project/{id}', name: 'admin_switch_project')]
    public function switchProject(int $id, Request $request, ProjectRepository $projectRepo): Response
    {
        $project = $projectRepo->find($id);

        if (!$project) {
            throw $this->createNotFoundException('Projecte no trobat');
        }

        $request->getSession()->set('_project_id', $project->getId());

        return $this->redirectToRoute('admin_dashboard');
    }
}
