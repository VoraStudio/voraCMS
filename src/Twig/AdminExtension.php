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

   Injectem Security i RequestStack per resoldre el context.
   =========================================================== */

namespace App\Twig;

use App\Entity\User;
use App\Repository\ContentTypeRepository;
use App\Repository\ProjectRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AdminExtension extends AbstractExtension implements GlobalsInterface
{
    public function __construct(
        private readonly ContentTypeRepository $ctRepo,
        private readonly ProjectRepository $projectRepo,
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_content_types', [$this, 'getContentTypes']),
            new TwigFunction('admin_active_project', [$this, 'getActiveProject']),
            new TwigFunction('admin_projects', [$this, 'getProjects']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'decodeJson']),
        ];
    }

    /* -----------------------------------------------------------
       getGlobals — Variables globals disponibles a totes les
       plantilles.
       - currentUser: l'usuari actual (o null si no autenticat)
       - activeProject: el projecte actiu seleccionat a sessió
       ----------------------------------------------------------- */
    public function getGlobals(): array
    {
        return [
            'currentUser' => $this->security->getUser(),
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
       decodeJson — Filtre Twig per deserialitzar JSON a array.
       Pensat per camps que emmagatzemen dades en format JSON
       (ex: date_range, location, etc).
       Retorna null si el valor no és un JSON vàlid o és buit.
       ----------------------------------------------------------- */
    public function decodeJson(?string $value): ?array
    {
        if (!$value) return null;
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : null;
    }

    public function getProjects(): array
    {
        $currentUser = $this->security->getUser();
        if (!$currentUser instanceof User) {
            return [];
        }

        $isAdmin = $this->security->isGranted('ROLE_ADMIN');
        if (!$isAdmin) {
            return $this->projectRepo->findBy(['user' => $currentUser], ['id' => 'DESC']);
        }

        $request = $this->requestStack->getCurrentRequest();
        $userId = $request ? $request->query->getInt('user', 0) : 0;
        if ($userId > 0) {
            return $this->projectRepo->findBy(['user' => $userId], ['id' => 'DESC']);
        }

        return $this->projectRepo->findAllOrderedByUser();
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
