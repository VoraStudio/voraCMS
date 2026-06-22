<?php

/* ===========================================================
   AdminExtension — Funcions Twig per al panell d'administració.
   La sidebar del panell mostra els ContentType del projecte
   actiu. Si l'usuari no ha seleccionat cap projecte, no en
   mostra cap (cas 2+ projectes — cal triar-ne un primer).

   Flux:
     1. Llegir _project_id de la sessió
     2. Si existeix → filtrar ContentTypes per aquest projecte
     3. Si no existeix → no mostrar res (forçar selecció)

   Injectem ClientScope i RequestStack per resoldre el context.
   =========================================================== */

namespace App\Twig;

use App\Repository\ContentTypeRepository;
use App\Repository\ProjectRepository;
use App\Service\ClientScope;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminExtension extends AbstractExtension
{
    public function __construct(
        private readonly ContentTypeRepository $ctRepo,
        private readonly ProjectRepository $projectRepo,
        private readonly ClientScope $clientScope,
        private readonly RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_content_types', [$this, 'getContentTypes']),
            new TwigFunction('admin_active_project', [$this, 'getActiveProject']),
        ];
    }

    /* -----------------------------------------------------------
       getGlobals — Variables globals disponibles a totes les
       plantilles.
       - currentClient: el client actual (o null per super-admin)
       - activeProject: el projecte actiu seleccionat a sessió
       ----------------------------------------------------------- */
    public function getGlobals(): array
    {
        return [
            'currentClient' => $this->clientScope->getClient(),
            'activeProject' => $this->getActiveProject(),
        ];
    }

    /* -----------------------------------------------------------
       getContentTypes — Retorna els ContentType actius del
       projecte seleccionat a sessió. Si no hi ha projecte
       actiu, retorna buit (cal triar-ne un primer).
       ----------------------------------------------------------- */
    public function getContentTypes(): array
    {
        $projectId = $this->getSessionProjectId();
        if ($projectId === null) {
            return [];
        }

        return $this->ctRepo->findActive($projectId);
    }

    /* -----------------------------------------------------------
       getActiveProject — Retorna l'objecte Project del projecte
       actiu a sessió, o null si no n'hi ha.
       ----------------------------------------------------------- */
    public function getActiveProject(): ?object
    {
        $projectId = $this->getSessionProjectId();
        if ($projectId === null) {
            return null;
        }

        return $this->projectRepo->find($projectId);
    }

    /* -----------------------------------------------------------
       getSessionProjectId — Helper: llegeix el projecte actiu
       de la sessió de l'usuari.
       ----------------------------------------------------------- */
    private function getSessionProjectId(): ?int
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request && $request->hasSession()) {
            return $request->getSession()->get('_project_id');
        }
        return null;
    }
}
