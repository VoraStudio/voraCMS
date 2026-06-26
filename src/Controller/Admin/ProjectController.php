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

use App\Entity\Project;
use App\Entity\User;
use App\Repository\ProjectRepository;
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
       Es mostren com a targetes (cards) per seleccionar-ne un.
       ----------------------------------------------------------- */
    #[Route('', name: 'admin_projects')]
    public function index(ProjectRepository $projectRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $projects = $projectRepo->findActive();

        return $this->render('admin/project/index.html.twig', [
            'projects' => $projects,
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
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Cal estar autenticat.');
        }

        $project = new Project();

        if ($request->isMethod('POST')) {
            $project->setName($request->request->get('name', ''));
            $project->setSlug($this->slugify($project->getName()));
            $project->setDescription($request->request->get('description', ''));
            $project->setColor($request->request->get('color', '#4945FF'));
            $project->setActive(true);
            $project->setUser($currentUser);

            $errors = $validator->validate($project);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->persist($project);
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
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $currentUser = $this->getUser();
        if (!$currentUser instanceof User) {
            throw $this->createAccessDeniedException('Cal estar autenticat.');
        }

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
        ]);
    }

    /* -----------------------------------------------------------
       delete — Elimina un projecte (només si no té contingut).
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_project_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Project $project,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        /* No permetem esborrar si té contingut associat */
        if (count($project->getContentTypes()) > 0) {
            $this->addFlash('error', 'No es pot eliminar un projecte amb seccions. Elimina primer les seccions.');
            return $this->redirectToRoute('admin_projects');
        }

        /* CSRF protection */
        if (!$this->isCsrfTokenValid('delete-project-' . $project->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token de seguretat invàlid.');
            return $this->redirectToRoute('admin_projects');
        }

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
