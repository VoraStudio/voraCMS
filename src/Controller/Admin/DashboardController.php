<?php

/* ===========================================================
   DashboardController — Panell principal d'administració.
   Comportament segons el rol:

   - ROLE_ADMIN: panell global amb mètriques agregades.
   - ROLE_MOD / ROLE_USUARIO: veu només el seu usuari i el
     seu projecte actiu amb les seccions i mètriques.

   Admin path:
     metrics → [ Projectes, Publicacions, Visites ]
     (el comptador de clients es deixa a 0 fins que s'actualitzi
     la plantilla a la fase de neteja de templates)
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\Entry;
use App\Entity\User;
use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use App\Repository\MediaRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Repository\VisitRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class DashboardController extends AbstractController
{
    /* -----------------------------------------------------------
       index
       ───────────────────────────────────────────────────────────
       ADMIN  → panell global amb projectes, publicacions i visites
       MOD/USUARIO → panell scoped al seu usuari/projecte actiu
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        Request $request,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        ProjectRepository $projectRepo,
        MediaRepository $mediaRepo,
        EntityManagerInterface $em,
        VisitRepository $visitRepo,
        UserRepository $userRepo,
    ): Response {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        /* ═══════════════════════════════════════════════════════
           ADMIN DASHBOARD — Global, tots els usuaris
           ═══════════════════════════════════════════════════════ */
        if ($isAdmin) {
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

            return $this->render('admin/dashboard.html.twig', [
                'isAdmin' => true,
                'metrics' => [
                    'totalUsuaris' => $userRepo->count([]),
                    'totalProyectos' => $projectRepo->count([]),
                    'totalPublicaciones' => $totalPublishedGlobally,
                    'totalVisites' => $totalVisitsToday,
                ],
                'latestUsers' => $userRepo->findBy([], ['createdAt' => 'DESC'], 5),
                'latestProjects' => $projectRepo->findBy([], ['createdAt' => 'DESC'], 5),
                'latestContentTypes' => $ctRepo->findLatestWithProject(5),
            ]);
        }

        /* ═══════════════════════════════════════════════════════
           MOD / USUARIO DASHBOARD — Scoped al seu usuari
           ═══════════════════════════════════════════════════════ */
        $session = $request->getSession();
        $activeProjectId = $session->get('_project_id');

        $projects = $isAdmin
            ? $projectRepo->findActive()
            : $projectRepo->findBy(['user' => $user, 'active' => true]);
        $totalProjects = count($projects);

        if ($totalProjects === 0) {
            return $this->redirectToRoute('admin_project_new');
        }

        // Establecer el primer proyecto activo si no hay ninguno en sesión
        if ($activeProjectId === null) {
            $session->set('_project_id', $projects[0]->getId());
            $activeProjectId = $projects[0]->getId();
        }

        /* ── Mètriques de cada projecte del usuari ── */
        $projectsData = [];
        $globalEntries = 0;
        $globalPublished = 0;

        foreach ($projects as $p) {
            $projEntries = 0;
            $projPublished = 0;

            foreach ($p->getContentTypes() as $ct) {
                if ($ct->isActive()) {
                    $projEntries += count($ct->getEntries());
                    foreach ($ct->getEntries() as $entry) {
                        if ($entry->getStatus() === Entry::STATUS_PUBLISHED) {
                            $projPublished++;
                        }
                    }
                }
            }

            $projectsData[] = [
                'project' => $p,
                'totalSections' => count($p->getContentTypes()),
                'totalEntries' => $projEntries,
                'totalPublished' => $projPublished,
            ];

            $globalEntries += $projEntries;
            $globalPublished += $projPublished;
        }

        $userId = $user?? null ? $user->getId() : null;
        $totalMedia = $userId !== null
            ? count($mediaRepo->findByUser($userId))
            : 0;

        return $this->render('admin/dashboard.html.twig', [
            'projectsData' => $projectsData,
            'activeProjectId' => $activeProjectId,
            'metrics' => [
                'totalEntries' => $globalEntries,
                'totalPublished' => $globalPublished,
                'totalMedia' => $totalMedia,
                'totalProjects' => $totalProjects,
            ],
        ]);
    }

    /* -----------------------------------------------------------
        switchProject — Canvia el projecte actiu a sessió.
        GET /admin/switch-project/{id}
          - ROLE_ADMIN → project show (cards de seccions)
          - ROLE_MOD/USUARIO → dashboard scoped
        ----------------------------------------------------------- */
    #[Route('/switch-project/{id}', name: 'admin_switch_project')]
    public function switchProject(int $id, Request $request, ProjectRepository $projectRepo): Response
    {
        $project = $projectRepo->find($id);

        if (!$project) {
            throw $this->createNotFoundException('Projecte no trobat');
        }

        $request->getSession()->set('_project_id', $project->getId());

        $redirect = $request->query->get('_redirect');
        if ($redirect && str_starts_with($redirect, '/admin/')) {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('admin_project_show', ['id' => $project->getId()]);
    }
}
