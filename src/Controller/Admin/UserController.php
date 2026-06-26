<?php

/* ===========================================================
   UserController — CRUD d'usuaris (només ROLE_ADMIN).

   Gestiona els usuaris del sistema (clients/tenants).
   Només l'admin pot crear, editar, activar/desactivar
   i eliminar usuaris.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\ProjectRepository;
use App\Repository\UserRepository;
use App\Service\SlugGenerator;
use App\Service\TokenGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/users')]
class UserController extends AbstractController
{
    /* -----------------------------------------------------------
       index — Llista d'usuaris.
       ----------------------------------------------------------- */
    #[Route('', name: 'admin_user_index')]
    public function index(UserRepository $userRepo, ProjectRepository $projectRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $users = $userRepo->findBy([], ['name' => 'ASC']);

        $userProjects = [];
        foreach ($users as $user) {
            $userProjects[$user->getId()] = $projectRepo->count(['user' => $user]);
        }

        return $this->render('admin/user/index.html.twig', [
            'users' => $users,
            'userProjects' => $userProjects,
        ]);
    }

    /* -----------------------------------------------------------
       new — Crear un usuari nou.
       ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_user_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        SlugGenerator $slugGenerator,
        TokenGenerator $tokenGenerator,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $user = new User();

        if ($request->isMethod('POST')) {
            $email = $request->request->get('email', '');
            $name = $request->request->get('name', '');
            $password = $request->request->get('password', '123');
            $company = $request->request->get('company', '');
            $role = $request->request->get('role', 'ROLE_USUARIO');
            $active = $request->request->getBoolean('active', true);

            $user->setEmail($email);
            $user->setName($name);
            $user->setCompany($company ?: null);
            $user->setSlug($slugGenerator->generate($company ?: $name));
            $user->setApiToken($tokenGenerator->generate(32));
            $user->setRoles([$role]);
            $user->setActive($active);
            $user->setPassword($hasher->hashPassword($user, $password));

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->persist($user);
                $em->flush();
                $this->addFlash('success', 'Usuari creat correctament.');
                return $this->redirectToRoute('admin_user_index');
            }
        }

        return $this->render('admin/user/form.html.twig', [
            'user' => $user,
            'isNew' => true,
        ]);
    }

    /* -----------------------------------------------------------
       edit — Editar un usuari existent.
       ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_user_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        User $user,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $hasher,
        ValidatorInterface $validator,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            $user->setEmail($request->request->get('email', $user->getEmail()));
            $user->setName($request->request->get('name', $user->getName()));
            $user->setCompany($request->request->get('company', $user->getCompany()) ?: null);
            $user->setRoles([$request->request->get('role', 'ROLE_USUARIO')]);
            $user->setActive($request->request->getBoolean('active', $user->isActive()));

            $password = $request->request->get('password', '');
            if (!empty($password)) {
                $user->setPassword($hasher->hashPassword($user, $password));
            }

            $errors = $validator->validate($user);
            if (count($errors) > 0) {
                foreach ($errors as $error) {
                    $this->addFlash('error', $error->getMessage());
                }
            } else {
                $em->flush();
                $this->addFlash('success', 'Usuari actualitzat correctament.');
                return $this->redirectToRoute('admin_user_index');
            }
        }

        return $this->render('admin/user/form.html.twig', [
            'user' => $user,
            'isNew' => false,
        ]);
    }

    /* -----------------------------------------------------------
       toggleActive — Activar/desactivar usuari.
       ----------------------------------------------------------- */
    #[Route('/{id}/toggle-active', name: 'admin_user_toggle_active', methods: ['POST'])]
    public function toggleActive(User $user, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'No pots desactivar el teu propi usuari.');
            return $this->redirectToRoute('admin_user_index');
        }

        $user->setActive(!$user->isActive());
        $em->flush();

        $msg = $user->isActive() ? 'Usuari activat.' : 'Usuari desactivat.';
        $this->addFlash('success', $msg);

        return $this->redirectToRoute('admin_user_index');
    }

    /* -----------------------------------------------------------
       delete — Eliminar un usuari.
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_user_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        User $user,
        EntityManagerInterface $em,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($user === $this->getUser()) {
            $this->addFlash('error', 'No pots eliminar el teu propi usuari.');
            return $this->redirectToRoute('admin_user_index');
        }

        if ($this->isCsrfTokenValid('delete-user-' . $user->getId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Usuari i totes les seves dades eliminats.');
        }

        return $this->redirectToRoute('admin_user_index');
    }
}
