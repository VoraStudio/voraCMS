<?php

/* ===========================================================
   AdminExtension — Funcions Twig per al panell d'administració.
   La sidebar del panell mostra els ContentType del client
   actual. Injectem ClientScope per resoldre el context i
   passar-lo explícitament al repositori.

   Això assegura que un client admin només vegi els seus
   tipus de contingut, i un super-admin vegi tots (quan
   ClientScope retorna null, no s'aplica filtre).
   =========================================================== */

namespace App\Twig;

use App\Repository\ContentTypeRepository;
use App\Service\ClientScope;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminExtension extends AbstractExtension
{
    public function __construct(
        private readonly ContentTypeRepository $ctRepo,
        private readonly ClientScope $clientScope,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_content_types', [$this, 'getContentTypes']),
        ];
    }

    /* -----------------------------------------------------------
       getGlobals — Variables globals disponibles a totes les
       plantilles. currentClient proporciona el client actual
       (o null per super-admin) a la sidebar i al dashboard.
       ----------------------------------------------------------- */
    public function getGlobals(): array
    {
        return [
            'currentClient' => $this->clientScope->getClient(),
        ];
    }

    /* -----------------------------------------------------------
       getContentTypes — Retorna els ContentType visibles per al
       client actual. Delega al repositori, que internament
       consulta ClientScope per aplicar el filtre adequat.

       Quan no hi ha client (super-admin), findActive() retorna
       tots els ContentType sense filtre.
       ----------------------------------------------------------- */
    public function getContentTypes(): array
    {
        return $this->ctRepo->findActive();
    }
}
