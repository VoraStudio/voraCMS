<?php

/* ===========================================================
   ProfileController — Fitxa de perfil de l'usuari connectat.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USUARIO')]
class ProfileController extends AbstractController
{
    #[Route('/admin/profile', name: 'admin_profile')]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('admin_login');
        }

        return $this->render('admin/user/profile.html.twig', [
            'user' => $user,
            'breadcrumbs' => [
                ['label' => 'nav.dashboard', 'url' => $this->generateUrl('admin_dashboard')],
                ['label' => 'profile.my_profile'],
            ],
        ]);
    }
}
