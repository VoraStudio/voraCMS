<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin')]
class LocaleController extends AbstractController
{
    #[Route('/switch-locale/{locale}', name: 'admin_switch_locale', methods: ['GET'])]
    public function switchLocale(string $locale, Request $request, EntityManagerInterface $em): Response
    {
        $supportedLocales = ['ca', 'es', 'en'];

        if (in_array($locale, $supportedLocales, true)) {
            $request->getSession()->set('_locale', $locale);
            $request->setLocale($locale);

            /** @var User|null $user */
            $user = $this->getUser();
            if ($user) {
                $user->setLocale($locale);
                $em->flush();
            }
        }

        $referer = $request->headers->get('referer');
        if ($referer && str_starts_with($referer, $request->getSchemeAndHttpHost())) {
            return $this->redirect($referer);
        }

        return $this->redirectToRoute('admin_dashboard');
    }
}
