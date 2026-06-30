<?php

/* ===========================================================
   ApiGuideController — Guia d'ús de l'API REST
   Mostra una pàgina d'ajuda amb endpoints, exemples i
   explicacions per al desenvolupador frontend.
   =========================================================== */

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/api-guide')]
class ApiGuideController extends AbstractController
{
    #[Route('', name: 'admin_api_guide')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USUARIO');

        return $this->render('admin/api-guide.html.twig');
    }
}
