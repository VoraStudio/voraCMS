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
use App\Repository\ApiRequestLogRepository;
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
        ApiRequestLogRepository $apiLogRepo,
    ): Response {
        $user = $this->getUser();
        $isAdmin = $this->isGranted('ROLE_ADMIN');

        $projectId = null;
        $clientId = null;

        if ($isAdmin) {
            $projParam = $request->query->get('projectId');
            if ($projParam && $projParam !== 'all') {
                $projectId = (int) $projParam;
            }
            $clientParam = $request->query->get('clientId');
            if ($clientParam && $clientParam !== 'all') {
                $clientId = (int) $clientParam;
            }
        } else {
            $clientId = $user ? $user->getId() : null;
            $session = $request->getSession();
            $projParam = $session->get('_project_id');
            if ($projParam) {
                $projectId = (int) $projParam;
                /* Validar que el projecte guardat a la sessió pertanyi realment al client actual */
                if ($clientId) {
                    $projObj = $projectRepo->find($projectId);
                    if (!$projObj || $projObj->getUser()?->getId() !== $clientId) {
                        $projectId = null;
                        $session->remove('_project_id');
                    }
                }
            }
        }

        $projects = $projectRepo->findAll();
        $clients = $userRepo->findAll();

        $filteredProjects = $projects;
        if ($clientId) {
            $filteredProjects = array_values(array_filter($projects, function ($p) use ($clientId) {
                return $p->getUser() && $p->getUser()->getId() === $clientId;
            }));
        }

        // --- KPIs: Visits ---
        $todayStart = new \DateTimeImmutable('today');
        $yesterdayStart = new \DateTimeImmutable('yesterday');

        $visitQb = $em->createQueryBuilder()
            ->select('COUNT(v.id)')
            ->from(\App\Entity\Visit::class, 'v');

        if ($projectId) {
            $visitQb->join('v.entry', 'e')
                    ->join('e.contentType', 'ct')
                    ->andWhere('ct.project = :projectId')
                    ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $visitQb->andWhere('v.user = :clientId')
                    ->setParameter('clientId', $clientId);
        }

        $visitQbToday = clone $visitQb;
        $visitsToday = (int) $visitQbToday->andWhere('v.visitedAt >= :today')
            ->setParameter('today', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $visitQbYesterday = clone $visitQb;
        $visitsYesterday = (int) $visitQbYesterday->andWhere('v.visitedAt >= :yesterday')
            ->andWhere('v.visitedAt < :today')
            ->setParameter('yesterday', $yesterdayStart)
            ->setParameter('today', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $visitTrend = 0.0;
        if ($visitsYesterday > 0) {
            $visitTrend = round((($visitsToday - $visitsYesterday) / $visitsYesterday) * 100, 1);
        }

        // --- Client KPIs: Weekly visits ---
        $weekStart = $todayStart->modify('-6 days');
        $visitQbWeek = clone $visitQb;
        $visitsWeek = (int) $visitQbWeek->andWhere('v.visitedAt >= :weekStart')
            ->setParameter('weekStart', $weekStart)
            ->getQuery()
            ->getSingleScalarResult();

        // Peak daily visits this week + weekly chart data
        $visitPeak = 0;
        $visitPeakDay = null;
        $weeklyVisits = [];
        $maxDailyVisits = 0;
        $dayNamesCa = ['Monday' => 'Dilluns', 'Tuesday' => 'Dimarts', 'Wednesday' => 'Dimecres', 'Thursday' => 'Dijous', 'Friday' => 'Divendres', 'Saturday' => 'Dissabte', 'Sunday' => 'Diumenge'];
        for ($i = 6; $i >= 0; $i--) {
            $dayStart = $todayStart->modify("-{$i} days");
            $dayEnd = $dayStart->modify('+1 day');
            $dayQb = clone $visitQb;
            $dayTotal = (int) $dayQb->andWhere('v.visitedAt >= :dayStart')
                ->andWhere('v.visitedAt < :dayEnd')
                ->setParameter('dayStart', $dayStart)
                ->setParameter('dayEnd', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();
            if ($dayTotal > $visitPeak) {
                $visitPeak = $dayTotal;
                $rawDay = $dayStart->format('l');
                $visitPeakDay = $dayNamesCa[$rawDay] ?? $rawDay;
            }
            if ($dayTotal > $maxDailyVisits) {
                $maxDailyVisits = $dayTotal;
            }
            $rawDayName = $dayStart->format('D');
            $shortDay = $dayNamesCa[$dayStart->format('l')] ?? $rawDayName;
            $weeklyVisits[] = [
                'day' => mb_substr($shortDay, 0, 2),
                'total' => $dayTotal
            ];
        }

        // Last visit timestamp
        $lastVisitQb = clone $visitQb;
        $lastVisitRow = $lastVisitQb->select('v.visitedAt')
            ->orderBy('v.visitedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        $lastVisit = $lastVisitRow ? $lastVisitRow['visitedAt'] : null;

        // Friendly last-visit string
        $lastVisitAgo = null;
        if ($lastVisit) {
            $now = new \DateTimeImmutable();
            $diff = $now->getTimestamp() - $lastVisit->getTimestamp();
            if ($diff < 60) {
                $lastVisitAgo = 'Fa uns segons';
            } elseif ($diff < 3600) {
                $mins = floor($diff / 60);
                $lastVisitAgo = "Fa $mins min";
            } elseif ($diff < 86400) {
                $hours = floor($diff / 3600);
                $lastVisitAgo = "Fa $hours h";
            } elseif ($diff < 604800) {
                $days = floor($diff / 86400);
                $lastVisitAgo = "Fa $days dies";
            } else {
                $lastVisitAgo = $lastVisit->format('d/m/Y');
            }
        }

        // Top pages this week
        $topPagesQb = $em->createQueryBuilder()
            ->select('v.path, COUNT(v.id) AS visitCount')
            ->from(\App\Entity\Visit::class, 'v')
            ->andWhere('v.visitedAt >= :weekStart')
            ->setParameter('weekStart', $weekStart);
        if ($projectId) {
            $topPagesQb->join('v.entry', 'e')
                       ->join('e.contentType', 'ct')
                       ->andWhere('ct.project = :projectId')
                       ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $topPagesQb->andWhere('v.user = :clientId')
                       ->setParameter('clientId', $clientId);
        }
        $topPages = $topPagesQb->groupBy('v.path')
            ->orderBy('visitCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // --- KPIs: API Requests ---
        $apiLogQb = $em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(\App\Entity\ApiRequestLog::class, 'r');

        if ($projectId) {
            $apiLogQb->andWhere('r.project = :projectId')
                     ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $apiLogQb->join('r.project', 'p')
                     ->andWhere('p.user = :clientId')
                     ->setParameter('clientId', $clientId);
        }

        $apiLogQbToday = clone $apiLogQb;
        $apiToday = (int) $apiLogQbToday->andWhere('r.createdAt >= :today')
            ->setParameter('today', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $apiLogQbYesterday = clone $apiLogQb;
        $apiYesterday = (int) $apiLogQbYesterday->andWhere('r.createdAt >= :yesterday')
            ->andWhere('r.createdAt < :today')
            ->setParameter('yesterday', $yesterdayStart)
            ->setParameter('today', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $apiTrend = 0.0;
        if ($apiYesterday > 0) {
            $apiTrend = round((($apiToday - $apiYesterday) / $apiYesterday) * 100, 1);
        }

        // Accepted & Denied today
        $acceptedQb = clone $apiLogQb;
        $acceptedToday = (int) $acceptedQb->andWhere('r.createdAt >= :today')
            ->andWhere('r.statusCode >= 200')
            ->andWhere('r.statusCode < 300')
            ->setParameter('today', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $deniedQb = clone $apiLogQb;
        $deniedToday = (int) $deniedQb->andWhere('r.createdAt >= :today')
            ->andWhere('r.statusCode >= 400')
            ->setParameter('today', $todayStart)
            ->getQuery()
            ->getSingleScalarResult();

        $successRate = 100.0;
        if ($apiToday > 0) {
            $successRate = round(($acceptedToday / $apiToday) * 100, 1);
        }

        // --- Weekly Charts ---
        $weeklyRequests = [];
        $weeklyAcceptedVsDenied = [];
        $dayNamesCa = ['Mon' => 'Dl', 'Tue' => 'Dt', 'Wed' => 'Dc', 'Thu' => 'Dj', 'Fri' => 'Dv', 'Sat' => 'Ds', 'Sun' => 'Dg'];

        $maxDailyRequests = 0;
        $maxDailyAcceptedDenied = 0;

        for ($i = 6; $i >= 0; $i--) {
            $dayStart = $todayStart->modify("-{$i} days");
            $dayEnd = $dayStart->modify('+1 day');

            $dayQb = clone $apiLogQb;
            $dayTotal = (int) $dayQb->andWhere('r.createdAt >= :dayStart')
                ->andWhere('r.createdAt < :dayEnd')
                ->setParameter('dayStart', $dayStart)
                ->setParameter('dayEnd', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            if ($dayTotal > $maxDailyRequests) {
                $maxDailyRequests = $dayTotal;
            }

            $dayAccQb = clone $apiLogQb;
            $dayAccepted = (int) $dayAccQb->andWhere('r.createdAt >= :dayStart')
                ->andWhere('r.createdAt < :dayEnd')
                ->andWhere('r.statusCode >= 200')
                ->andWhere('r.statusCode < 300')
                ->setParameter('dayStart', $dayStart)
                ->setParameter('dayEnd', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            $dayDenQb = clone $apiLogQb;
            $dayDenied = (int) $dayDenQb->andWhere('r.createdAt >= :dayStart')
                ->andWhere('r.createdAt < :dayEnd')
                ->andWhere('r.statusCode >= 400')
                ->setParameter('dayStart', $dayStart)
                ->setParameter('dayEnd', $dayEnd)
                ->getQuery()
                ->getSingleScalarResult();

            if (($dayAccepted + $dayDenied) > $maxDailyAcceptedDenied) {
                $maxDailyAcceptedDenied = $dayAccepted + $dayDenied;
            }

            $rawDayName = $dayStart->format('D');
            $dayName = $dayNamesCa[$rawDayName] ?? $rawDayName;

            $weeklyRequests[] = [
                'day' => $dayName,
                'total' => $dayTotal
            ];

            $weeklyAcceptedVsDenied[] = [
                'day' => $dayName,
                'accepted' => $dayAccepted,
                'denied' => $dayDenied
            ];
        }

        // --- Endpoints ---
        $endpointQb = $em->createQueryBuilder()
            ->select('r.method, r.endpoint, COUNT(r.id) AS total')
            ->from(\App\Entity\ApiRequestLog::class, 'r');

        if ($projectId) {
            $endpointQb->andWhere('r.project = :projectId')
                       ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $endpointQb->join('r.project', 'p')
                       ->andWhere('p.user = :clientId')
                       ->setParameter('clientId', $clientId);
        }

        $topEndpoints = $endpointQb->groupBy('r.method, r.endpoint')
            ->orderBy('total', 'DESC')
            ->setMaxResults(4)
            ->getQuery()
            ->getResult();

        // --- Recent Files ---
        $mediaQb = $em->createQueryBuilder()
            ->select('m')
            ->from(\App\Entity\Media::class, 'm');

        if ($projectId) {
            $mediaQb->andWhere('m.project = :projectId')
                    ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $mediaQb->andWhere('m.user = :clientId')
                    ->setParameter('clientId', $clientId);
        }

        $recentFiles = $mediaQb->orderBy('m.createdAt', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // --- Storage ---
        $storageQb = $em->createQueryBuilder()
            ->select('SUM(m.fileSize) AS totalSize, COUNT(m.id) AS totalFiles')
            ->from(\App\Entity\Media::class, 'm');

        if ($projectId) {
            $storageQb->andWhere('m.project = :projectId')
                      ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $storageQb->andWhere('m.user = :clientId')
                      ->setParameter('clientId', $clientId);
        }

        $storageData = $storageQb->getQuery()->getSingleResult();
        $totalBytes = (float) ($storageData['totalSize'] ?? 0);
        $totalFiles = (int) ($storageData['totalFiles'] ?? 0);

        $totalGB = round($totalBytes / (1024 * 1024 * 1024), 2);
        $totalMB = round($totalBytes / (1024 * 1024), 1);

        // --- Resource distribution ---
        $imagesCountQb = $em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(\App\Entity\Media::class, 'm')
            ->where("m.mimeType LIKE 'image/%'");

        if ($projectId) {
            $imagesCountQb->andWhere('m.project = :projectId')
                          ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $imagesCountQb->andWhere('m.user = :clientId')
                          ->setParameter('clientId', $clientId);
        }
        $imagesCount = (int) $imagesCountQb->getQuery()->getSingleScalarResult();

        $entriesCountQb = $em->createQueryBuilder()
            ->select('COUNT(e.id)')
            ->from(\App\Entity\Entry::class, 'e');

        if ($projectId) {
            $entriesCountQb->join('e.contentType', 'ct')
                           ->andWhere('ct.project = :projectId')
                           ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $entriesCountQb->andWhere('e.user = :clientId')
                           ->setParameter('clientId', $clientId);
        }
        $entriesCount = (int) $entriesCountQb->getQuery()->getSingleScalarResult();

        $otherFilesQb = $em->createQueryBuilder()
            ->select('COUNT(m.id)')
            ->from(\App\Entity\Media::class, 'm')
            ->where("m.mimeType NOT LIKE 'image/%'");

        if ($projectId) {
            $otherFilesQb->andWhere('m.project = :projectId')
                         ->setParameter('projectId', $projectId);
        } elseif ($clientId) {
            $otherFilesQb->andWhere('m.user = :clientId')
                         ->setParameter('clientId', $clientId);
        }
        $otherFilesCount = (int) $otherFilesQb->getQuery()->getSingleScalarResult();

        $totalItems = $imagesCount + $entriesCount + $otherFilesCount;
        $imagesPct = 0;
        $entriesPct = 0;
        $otherPct = 0;

        if ($totalItems > 0) {
            $imagesPct = (int) round(($imagesCount / $totalItems) * 100);
            $entriesPct = (int) round(($entriesCount / $totalItems) * 100);
            $otherPct = 100 - ($imagesPct + $entriesPct);
        }

        // --- Clients list ---
        $latestUsersData = [];
        $usersList = $userRepo->findBy([], ['createdAt' => 'DESC'], 4);
        $colors = ['#f0a048', '#3b82f6', '#a855f7', '#10b981'];

        foreach ($usersList as $index => $u) {
            $uProjects = $u->getProjects();
            $sectionsCount = 0;
            foreach ($uProjects as $p) {
                $sectionsCount += count($p->getContentTypes());
            }

            $latestUsersData[] = [
                'user' => $u,
                'color' => $colors[$index % count($colors)],
                'projectsCount' => count($uProjects),
                'sectionsCount' => $sectionsCount,
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'isAdmin' => $isAdmin,
            'projects' => $projects,
            'clients' => $clients,
            'filteredProjects' => $filteredProjects,
            'selectedProjectId' => $projectId,
            'selectedClientId' => $clientId,

            'visitsToday' => $visitsToday,
            'visitTrend' => $visitTrend,
            'visitsWeek' => $visitsWeek,
            'visitPeak' => $visitPeak,
            'visitPeakDay' => $visitPeakDay,
            'lastVisit' => $lastVisit,
            'lastVisitAgo' => $lastVisitAgo,
            'topPages' => $topPages,
            'weeklyVisits' => $weeklyVisits,
            'maxDailyVisits' => $maxDailyVisits,

            'apiToday' => $apiToday,
            'apiTrend' => $apiTrend,
            'acceptedToday' => $acceptedToday,
            'deniedToday' => $deniedToday,
            'successRate' => $successRate,
            'weeklyRequests' => $weeklyRequests,
            'weeklyAcceptedVsDenied' => $weeklyAcceptedVsDenied,
            'maxDailyRequests' => $maxDailyRequests,
            'maxDailyAcceptedDenied' => $maxDailyAcceptedDenied,

            'topEndpoints' => $topEndpoints,
            'recentFiles' => $recentFiles,

            'totalGB' => $totalGB,
            'totalMB' => $totalMB,
            'totalFiles' => $totalFiles,
            'imagesPct' => $imagesPct,
            'entriesPct' => $entriesPct,
            'otherPct' => $otherPct,

            'latestEntries' => [],
            'latestClients' => $latestUsersData,
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

        /* ─── Protecció: només administradors o el propietari poden activar el projecte ─── */
        $currentUser = $this->getUser();
        if (!$this->isGranted('ROLE_ADMIN') && $currentUser instanceof User) {
            if ($project->getUser()?->getId() !== $currentUser->getId()) {
                throw $this->createAccessDeniedException('No tens accés a aquest projecte.');
            }
        }

        $request->getSession()->set('_project_id', $project->getId());

        $redirect = $request->query->get('_redirect');
        if ($redirect && str_starts_with($redirect, '/admin/')) {
            return $this->redirect($redirect);
        }

        return $this->redirectToRoute('admin_project_show', ['id' => $project->getId()]);
    }
}
