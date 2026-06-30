<?php

/* ══════════════════════════════════════════════════════════════
   ProjectController — CRUD de projectes.

   Cada usuari pot tenir un o múltiples projectes. Cada projecte
   agrupa els seus propis tipus de contingut (seccions) i les
   seves entrades.

   Flux d'usuari:
    - 1 projecte → s'auto-selecciona, no veu aquesta pantalla
    - 2+ projectes → veu targetes amb cada projecte per triar

   Rutes:
     GET  /admin/projects          → Llista de projectes (cards)
     GET  /admin/projects/new      → Formulari de creació
     POST /admin/projects/new      → Crear projecte
     GET  /admin/projects/{id}/edit → Editar projecte
     POST /admin/projects/{id}/edit → Guardar canvis
     POST /admin/projects/{id}/delete → Eliminar
   ══════════════════════════════════════════════════════════════ */

namespace App\Controller\Admin;

use App\Entity\ContentType;
use App\Entity\FieldDefinition;
use App\Entity\Project;
use App\Entity\User;
use App\Repository\ContentTypeRepository;
use App\Repository\EntryRepository;
use App\Repository\MediaRepository;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/projects')]
class ProjectController extends AbstractController
{
    /* -----------------------------------------------------------
       index — Llista de projectes.
       Si es passa ?user={id}, filtra per usuari (des de la
       taula d'usuaris).
       ----------------------------------------------------------- */
    #[Route('', name: 'admin_projects')]
    public function index(
        Request $request,
        ProjectRepository $projectRepo,
        UserRepository $userRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $userId = $request->query->getInt('user', 0);
        $filterUser = null;
        $mainProject = null;
        $otherProjects = [];

        if ($userId > 0) {
            $filterUser = $userRepo->find($userId);
            $projects = $projectRepo->findBy(['user' => $filterUser], ['id' => 'DESC']);
            if (!empty($projects)) {
                $mainProject = $projects[0];
                $otherProjects = array_slice($projects, 1);
            }
        } else {
            $projects = $projectRepo->findAllOrderedByUser();
        }

        /* Compute projectes que NO es poden eliminar (únics del seu usuari) */
        $userCounts = [];
        foreach ($projects as $p) {
            $uid = $p->getUser()?->getId();
            if ($uid !== null) {
                $userCounts[$uid] = ($userCounts[$uid] ?? 0) + 1;
            }
        }
        $protectedProjectIds = [];
        foreach ($projects as $p) {
            $uid = $p->getUser()?->getId();
            if ($uid !== null && ($userCounts[$uid] ?? 0) === 1) {
                $protectedProjectIds[] = $p->getId();
            }
        }

        return $this->render('admin/project/index.html.twig', [
            'projects' => $projects,
            'mainProject' => $mainProject,
            'otherProjects' => $otherProjects,
            'filterUser' => $filterUser,
            'protectedProjectIds' => $protectedProjectIds,
        ]);
    }

    /* -----------------------------------------------------------
        show — Vista detall d'un projecte amb les seves seccions.
        Mostra cards per cada ContentType (ex: Eventos, Noticias)
        amb el nombre d'entrades i accés a gestió.
        ----------------------------------------------------------- */
    #[Route('/{id}', name: 'admin_project_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(
        int $id,
        Request $request,
        ProjectRepository $projectRepo,
        ContentTypeRepository $ctRepo,
        EntryRepository $entryRepo,
        MediaRepository $mediaRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $project = $projectRepo->find($id);
        if (!$project) {
            throw $this->createNotFoundException('Projecte no trobat');
        }

        $request->getSession()->set('_project_id', $project->getId());

        $contentTypes = $ctRepo->findActive($project->getId());
        $sections = [];
        foreach ($contentTypes as $ct) {
            $entries = $ct->getEntries();
            $thumbnails = [];
            foreach ($entries as $entry) {
                $thumb = null;
                foreach ($entry->getFieldValues() as $fv) {
                    $type = $fv->getFieldDefinition()?->getFieldType();
                    $val = $fv->getValue();
                    if (($type === FieldDefinition::TYPE_IMAGE || $type === FieldDefinition::TYPE_GALLERY) && $val) {
                        $ids = array_filter(explode(',', $val));
                        if (!empty($ids) && is_numeric($ids[0])) {
                            $media = $mediaRepo->find((int) $ids[0]);
                            if ($media) { $thumb = $media->getPath(); break; }
                        }
                    }
                }
                $thumbnails[$entry->getId()] = $thumb;
            }

            $sections[] = [
                'type' => $ct,
                'entries' => $entries,
                'thumbnails' => $thumbnails,
                'total' => count($entries),
                'published' => count($entryRepo->findPublishedByType($ct->getSlug())),
            ];
        }

        return $this->render('admin/project/show.html.twig', [
            'project' => $project,
            'sections' => $sections,
        ]);
    }

    /* -----------------------------------------------------------
        new — Mostra el formulari de creació d'un nou projecte.
        ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_project_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserRepository $userRepo,
        ContentTypeRepository $ctRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Cal estar autenticat.');
        }

        $project = new Project();
        $users = $userRepo->findBy([], ['name' => 'ASC']);

        $preselectUserId = $request->query->getInt('user', 0);

        if ($request->isMethod('POST')) {
            $project->setName($request->request->get('name', ''));
            $project->setSlug($this->slugify($project->getName()));
            $project->setDescription($request->request->get('description', ''));
            $project->setColor($request->request->get('color', '#4945FF'));
            $project->setActive(true);

            $targetUserId = $request->request->getInt('user_id', $currentUser->getId());
            $targetUser = $userRepo->find($targetUserId) ?: $currentUser;
            $project->setUser($targetUser);

            $errors = $validator->validate($project);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->persist($project);
                $em->flush();

                /* Clonar plantilles base al projecte */
                $baseTemplates = $ctRepo->findBaseTemplates();
                foreach ($baseTemplates as $template) {
                    $ct = new ContentType();
                    $ct->setName($template->getName());
                    $ct->setSlug($template->getSlug());
                    $ct->setDescription($template->getDescription());
                    $ct->setActive(true);
                    $ct->setBase(false);
                    $ct->setUser($targetUser);
                    $ct->setProject($project);

                    foreach ($template->getFields() as $field) {
                        $fd = new FieldDefinition();
                        $fd->setName($field->getName());
                        $fd->setSlug($field->getSlug());
                        $fd->setFieldType($field->getFieldType());
                        $fd->setRequired($field->isRequired());
                        $fd->setSortOrder($field->getSortOrder());
                        $ct->addField($fd);
                    }

                    $em->persist($ct);
                }
                $em->flush();

                /* Seleccionem el projecte acabat de crear */
                $request->getSession()->set('_project_id', $project->getId());

                $this->addFlash('success', 'Projecte creat correctament.');
                return $this->redirectToRoute('admin_projects');
            }
        }

        return $this->render('admin/project/form.html.twig', [
            'project' => $project,
            'isNew' => true,
            'currentUser' => $currentUser,
            'users' => $users,
            'preselectUserId' => $preselectUserId,
        ]);
    }

    /* -----------------------------------------------------------
       edit — Edita un projecte existent.
       ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_project_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Project $project,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
        UserRepository $userRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Cal estar autenticat.');
        }

        $users = $userRepo->findBy([], ['name' => 'ASC']);

        if ($request->isMethod('POST')) {
            /* CSRF protection */
            if (!$this->isCsrfTokenValid('project-edit-' . $project->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token de seguretat invàlid.');
                return $this->redirectToRoute('admin_projects');
            }

            $project->setName($request->request->get('name', $project->getName()));
            $project->setSlug($this->slugify($project->getName()));
            $project->setDescription($request->request->get('description', ''));
            $project->setColor($request->request->get('color', $project->getColor() ?? '#4945FF'));
            $project->setActive((bool) $request->request->get('active', true));

            $targetUserId = $request->request->getInt('user_id');
            if ($targetUserId > 0) {
                $targetUser = $userRepo->find($targetUserId);
                if ($targetUser) {
                    $project->setUser($targetUser);
                }
            }

            $errors = $validator->validate($project);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->flush();
                $this->addFlash('success', 'Projecte actualitzat.');
                return $this->redirectToRoute('admin_projects');
            }
        }

        return $this->render('admin/project/form.html.twig', [
            'project' => $project,
            'isNew' => false,
            'currentUser' => $currentUser,
            'users' => $users,
        ]);
    }

    /* -----------------------------------------------------------
       delete — Elimina un projecte amb totes les seves seccions.
       No permet eliminar si és l'únic projecte de l'usuari.
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_project_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Project $project,
        EntityManagerInterface $em,
        ProjectRepository $projectRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /* No permetem esborrar si és l'únic projecte del seu usuari */
        $user = $project->getUser();
        if ($user && $projectRepo->countByUser($user) <= 1) {
            $this->addFlash('error', 'No es pot eliminar l\'únic projecte de l\'usuari.');
            return $this->redirectToRoute('admin_projects');
        }

        /* CSRF protection */
        if (!$this->isCsrfTokenValid('delete-project-' . $project->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguretat invàlid.');
            return $this->redirectToRoute('admin_projects');
        }

        /* El cascading de Doctrine s'encarrega de seccions i entrades */
        $em->remove($project);
        $em->flush();

        /* Si el projecte eliminat era l'actiu, netegem la sessió */
        if ($request->getSession()->get('_project_id') === $project->getId()) {
            $request->getSession()->remove('_project_id');
        }

        $this->addFlash('success', 'Projecte eliminat.');
        return $this->redirectToRoute('admin_projects');
    }

    /* -----------------------------------------------------------
       toggleActive — Activa/desactiva un projecte.
       ----------------------------------------------------------- */
    #[Route('/{id}/toggle-active', name: 'admin_project_toggle_active', methods: ['POST'])]
    public function toggleActive(Request $request, Project $project, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if (!$this->isCsrfTokenValid('toggle-active-' . $project->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguretat invàlid.');
            return $this->redirectToRoute('admin_projects');
        }

        $project->setActive(!$project->isActive());
        $em->flush();

        $this->addFlash('success', $project->isActive() ? 'Projecte activat.' : 'Projecte desactivat.');
        return $this->redirectToRoute('admin_projects');
    }

    /* ─── Converteix un text en slug net ─── */
    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = str_replace(
            ['á','é','í','ó','ú','à','è','ì','ò','ù','ñ','ü'],
            ['a','e','i','o','u','a','e','i','o','u','n','u'],
            $text
        );
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }
}
