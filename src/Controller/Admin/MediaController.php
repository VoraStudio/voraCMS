<?php

/* ===========================================================
   MediaController — Gestió de la mediateca amb tenant isolation.

   Admin: vista agrupada per client (User.company) → projecte.
   Upload permet triar el projecte destí.
   Usuaris normals: només els seus fitxers, vista plana.
   =========================================================== */

namespace App\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Media;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\MediaRepository;
use App\Repository\ProjectRepository;
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
       index — Llista els fitxers multimèdia.
       Admin: agrupat per client (company) → projecte.
       MOSTRA TOTS ELS PROJECTES de cada client, fins i tot
       si no tenen media. Els media sense projecte van a
       "Sense projecte" dins del client corresponent.
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_media_index')]
    public function index(MediaRepository $repo, ProjectRepository $projectRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        if ($this->isGranted('ROLE_ADMIN')) {
            /* Indexar media per project_id i construir estructura
               des dels projectes (TOTS), ignorant media sense projecte */
            $allMedia = $repo->findAllWithUserOrdered();
            $mediaByProject = [];
            foreach ($allMedia as $m) {
                $pid = $m->getProject()?->getId();
                if ($pid) {
                    $mediaByProject[$pid][] = $m;
                }
            }

            $allProjects = $projectRepo->findBy([], ['name' => 'ASC']);
            $grouped = [];

            foreach ($allProjects as $p) {
                $pu = $p->getUser();
                if (!$pu) { continue; }
                $company = $pu->getCompany() ?: $pu->getName();
                $pKey = 'p_' . $p->getId();

                if (!isset($grouped[$company])) {
                    $grouped[$company] = ['company' => $company, 'projects' => []];
                }

                $items = $mediaByProject[$p->getId()] ?? [];
                $grouped[$company]['projects'][$pKey] = [
                    'project' => $p,
                    'label'   => $p->getName(),
                    'color'   => $p->getColor(),
                    'items'   => $items,
                    'count'   => count($items),
                ];
            }

            $media = $grouped;
            $groupedMode = true;
        } else {
            /* Usuari normal: media agrupat pels SEUS projectes */
            $user = $this->getUser();
            if (!$user instanceof User) {
                $media = [];
                $groupedMode = false;
            } else {
                $userMedia = $repo->findByUserProjects($user);
                $mediaByPid = [];
                foreach ($userMedia as $m) {
                    $pid = $m->getProject()?->getId();
                    if ($pid) { $mediaByPid[$pid][] = $m; }
                }

                $userProjects = $user->getProjects();
                $projects = [];
                foreach ($userProjects as $p) {
                    $items = $mediaByPid[$p->getId()] ?? [];
                    $projects['p_' . $p->getId()] = [
                        'project' => $p,
                        'label'   => $p->getName(),
                        'color'   => $p->getColor(),
                        'items'   => $items,
                        'count'   => count($items),
                    ];
                }
                $company = $user->getCompany() ?: 'Els meus projectes';
                $media = [$company => ['company' => $company, 'projects' => $projects]];
                $groupedMode = true;
            }
        }

        /* Projectes agrupats per client → usuari per al selector d'upload */
        $projectGroups = [];
        if ($this->isGranted('ROLE_ADMIN')) {
            $allProjects = $projectRepo->findBy([], ['name' => 'ASC']);
            foreach ($allProjects as $p) {
                $u = $p->getUser();
                if (!$u) { continue; }
                $company = $u->getCompany() ?: 'Sense client';
                $userLabel = $u->getName() . ' (' . $u->getEmail() . ')';
                $projectGroups[$company][$userLabel][] = $p;
            }
        } elseif ($this->getUser() instanceof User) {
            /* Per usuaris normals: només els seus projectes */
            $myLabel = $this->getUser()->getName() . ' (' . $this->getUser()->getEmail() . ')';
            $myCompany = $this->getUser()->getCompany() ?: 'Els meus projectes';
            $projectGroups[$myCompany][$myLabel] = iterator_to_array($this->getUser()->getProjects());
        }

        return $this->render('admin/media/index.html.twig', [
            'media'         => $media,
            'groupedMode'   => $groupedMode,
            'projectGroups' => $projectGroups,
        ]);
    }

    #[Route('/upload', name: 'admin_media_upload', methods: ['POST'])]
    public function upload(Request $request, MediaService $mediaService, ProjectRepository $projectRepo): JsonResponse
    {
        $user = $this->getUser();

        /* Resoldre projecte si s'ha enviat */
        $project = null;
        $projectId = $request->request->get('project_id');
        if ($projectId) {
            $project = $projectRepo->find($projectId);
            if ($project !== null && !$this->isGranted('ROLE_ADMIN')) {
                if ($project->getUser()?->getId() !== $user?->getId()) {
                    return $this->json(['error' => 'No tens permís sobre aquest projecte.'], 403);
                }
            }
        }

        /* Suport múltiples fitxers (files[0], files[1]…) o un de sol (file) */
        $files = [];

        /* Cas A: array de fitxers (files[]) */
        $fileArray = $request->files->get('files', []);
        if (is_array($fileArray)) {
            $files = array_values(array_filter($fileArray, fn($f) => $f instanceof UploadedFile));
        }

        /* Cas B: fitxer individual (file) — retrocompatibilitat */
        if (empty($files)) {
            $single = $request->files->get('file');
            if ($single instanceof UploadedFile) {
                $files = [$single];
            }
        }

        /* Cas C: escombrar TOTS els fitxers del request per si venen indexats (files[0], files[1]…) */
        if (empty($files)) {
            foreach ($request->files->all() as $value) {
                if ($value instanceof UploadedFile) {
                    $files[] = $value;
                } elseif (is_array($value)) {
                    foreach ($value as $v) {
                        if ($v instanceof UploadedFile) {
                            $files[] = $v;
                        }
                    }
                }
            }
        }

        if (empty($files)) {
            return $this->json(['error' => 'No s\'ha rebut cap fitxer.'], 400);
        }

        $results = [];
        $errors = [];

        foreach ($files as $file) {
            try {
                $media = $mediaService->upload($file, $user, $project);
                $results[] = [
                    'id'         => $media->getId(),
                    'url'        => $media->getPath(),
                    'filename'   => $media->getOriginalFilename(),
                    'project_id' => $project?->getId(),
                ];
            } catch (\InvalidArgumentException $e) {
                $errors[] = ['filename' => $file->getClientOriginalName(), 'error' => $e->getMessage()];
            }
        }

        $response = ['uploaded' => $results];
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return $this->json($response);
    }

    /* -----------------------------------------------------------
       delete — Elimina un fitxer multimèdia.
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_media_delete', methods: ['POST'])]
    public function delete(Request $request, Media $media, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        if (!$this->isGranted('ROLE_ADMIN')) {
            $user = $this->getUser();
            if (!$user instanceof User || $media->getUser()?->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException('No tens permís per eliminar aquesta imatge.');
            }
        }

        if ($this->isCsrfTokenValid('delete' . $media->getId(), $request->request->get('_token'))) {
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
       batchMove — Mou múltiples imatges a un altre projecte.
       ----------------------------------------------------------- */
    #[Route('/batch-move', name: 'admin_media_batch_move', methods: ['POST'])]
    public function batchMove(Request $request, EntityManagerInterface $em, ProjectRepository $projectRepo): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('batch-move', $request->request->get('_token'))) {
            return $this->json(['error' => 'Token invàlid.'], 403);
        }

        $mediaIds = $request->request->all('media_ids');
        $projectId = $request->request->get('project_id');

        if (empty($mediaIds)) {
            return $this->json(['error' => 'No s\'han seleccionat imatges.'], 400);
        }

        $project = null;
        if ($projectId) {
            $project = $projectRepo->find($projectId);
            if (!$project) {
                return $this->json(['error' => 'Projecte no trobat.'], 404);
            }
        }

        $mediaItems = $em->getRepository(Media::class)->findBy(['id' => $mediaIds]);

        if (empty($mediaItems)) {
            return $this->json(['error' => 'No s\'han trobat les imatges.'], 404);
        }

        foreach ($mediaItems as $media) {
            $media->setProject($project);
        }

        $em->flush();

        return $this->json([
            'ok'     => true,
            'moved'  => count($mediaItems),
            'to'     => $project?->getName() ?? 'Sense projecte',
        ]);
    }

    /* -----------------------------------------------------------
       picker — Modal de selecció de fitxers multimèdia.
       ----------------------------------------------------------- */
    #[Route('/picker', name: 'admin_media_picker')]
    public function picker(Request $request, MediaRepository $repo, ProjectRepository $projectRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        /* Projecte contextual (des de l'entrada) */
        $project = null;
        $projectId = $request->query->get('project_id');
        if ($projectId) {
            $project = $projectRepo->find($projectId);
        }

        /* Indexar media per project_id */
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            $allMedia = $repo->findAllWithUserOrdered();
        } elseif ($user instanceof User) {
            $allMedia = $repo->findByUserProjects($user);
        } else {
            $allMedia = [];
        }

        $mediaByPid = [];
        $mediaNoProject = [];
        foreach ($allMedia as $m) {
            $pid = $m->getProject()?->getId();
            if ($pid) {
                $mediaByPid[$pid][] = $m;
            } else {
                $mediaNoProject[] = $m;
            }
        }

        /* Obtenir TOTS els projectes per construir l'estructura completa */
        $groups = [];

        if ($this->isGranted('ROLE_ADMIN')) {
            $allProjects = $projectRepo->findBy([], ['name' => 'ASC']);
        } elseif ($user instanceof User) {
            $allProjects = $user->getProjects()->toArray();
        } else {
            $allProjects = [];
        }

        foreach ($allProjects as $p) {
            $company = $p->getUser()?->getCompany() ?: 'Sense client';
            $companyKey = strtolower($company);

            if (!isset($groups[$companyKey])) {
                $groups[$companyKey] = ['company' => $company, 'projects' => []];
            }

            $groups[$companyKey]['projects'][] = [
                'project' => $p,
                'items'   => $mediaByPid[$p->getId()] ?? [],
            ];
        }

        /* Afegir media sense projecte (si n'hi ha) */
        if (!empty($mediaNoProject)) {
            $companyKey = '_sense';
            $groups[$companyKey] = ['company' => 'Sense client', 'projects' => []];
            $groups[$companyKey]['projects'][] = [
                'project' => null,
                'items'   => $mediaNoProject,
            ];
        }

        /* Ordenar: per nom de companyia */
        usort($groups, fn($a, $b) => strcmp($a['company'], $b['company']));

        return $this->render('admin/media/picker.html.twig', [
            'mediaGrouped' => $groups,
            'fieldId'      => $request->query->get('field', ''),
            'multiple'     => $request->query->get('multiple', 'false') === 'true',
            'project'      => $project,
        ]);
    }
}
