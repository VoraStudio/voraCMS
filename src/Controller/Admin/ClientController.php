<?php

/* ===========================================================
   ClientController — Gestió de clients (CRUD).
   EXCLUSIU per a ROLE_SUPER_ADMIN. Tots els mètodes
   requereixen aquest rol.

   Els clients són la unitat d'aïllament multi-tenant.
   Cada client té els seus propis ContentTypes, Entries,
   Media i Users independents.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Repository\ClientRepository;
use App\Service\ClientScope;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/client')]
class ClientController extends AbstractController
{
    public function __construct(
        private readonly ClientScope $clientScope,
        private readonly EntityManagerInterface $em,
        private readonly ClientRepository $clientRepo,
    ) {}

    /* -----------------------------------------------------------
       index — Llista tots els clients amb estadístiques bàsiques
       (nombre d'entrades per client). Només accessible per
       super-admin.
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_client_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        /* Recuperem tots els clients amb el recompte d'entrades.
           Fem servir el repositori de clients i iterem per calcular
           les estadístiques. */
        $clients = $this->clientRepo->findBy([], ['name' => 'ASC']);

        $stats = [];
        foreach ($clients as $client) {
            $stats[] = [
                'client' => $client,
                'entries' => $client->getEntries()->count(),
                'users' => $client->getUsers()->count(),
                'contentTypes' => $client->getContentTypes()->count(),
            ];
        }

        return $this->render('admin/client/index.html.twig', [
            'stats' => $stats,
        ]);
    }

    /* -----------------------------------------------------------
       new — Formulari de creació de client (GET + POST).
       En POST crea l'entitat Client i la persisteix.

       TODO (Phase 6): Quan ClientProvisioner existeixi, cridar-lo
       aquí per provisionar els ContentType base del nou client.
       Exemple:
         $this->clientProvisioner->provision($client);
       Després de $em->persist($client) i abans del flush.
       ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $client = new Client();
            $client->setName($request->request->get('name'));
            $client->setSlug($request->request->get('slug'));
            $client->setLogo($request->request->get('logo') ?: null);
            $client->setActive($request->request->getBoolean('active'));

            $this->em->persist($client);

            /* ── TODO Phase 6: Provisionar ContentTypes base ──
               Quan ClientProvisioner estigui implementat:
               $this->clientProvisioner->provision($client);
               Això crearà els tipus de contingut per defecte
               (pàgines, notícies, etc.) per al nou client.
               ──────────────────────────────────────────────── */

            $this->em->flush();

            $this->addFlash('success', 'Client creat correctament.');
            return $this->redirectToRoute('admin_client_index');
        }

        return $this->render('admin/client/new.html.twig');
    }

    /* -----------------------------------------------------------
       edit — Formulari d'edició de client (GET + POST).
       Carrega les dades del client existents i permet
       modificar-les.
       ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        if ($request->isMethod('POST')) {
            $client->setName($request->request->get('name'));
            $client->setLogo($request->request->get('logo') ?: null);
            $client->setActive($request->request->getBoolean('active'));

            /* El slug NO es pot canviar perquè és la clau d'URL
               i s'utilitza en rutes públiques. Canviar-lo trencaria
               enllaços existents. */

            $this->em->flush();

            $this->addFlash('success', 'Client actualitzat correctament.');
            return $this->redirectToRoute('admin_client_index');
        }

        return $this->render('admin/client/edit.html.twig', [
            'client' => $client,
        ]);
    }

    /* -----------------------------------------------------------
       delete — Elimina un client (POST amb CSRF).
       ATENCIÓ: L'eliminació en cascada elimina tots els
       ContentTypes, Entries, Media i Users associats.
       No es pot desfer.
       ----------------------------------------------------------- */
    #[Route('/{id}/delete', name: 'admin_client_delete', methods: ['POST'])]
    public function delete(Request $request, Client $client): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPER_ADMIN');

        $tokenId = 'delete_client_' . $client->getId();
        if ($this->isCsrfTokenValid($tokenId, $request->request->get('_token'))) {
            $this->em->remove($client);
            $this->em->flush();
            $this->addFlash('success', 'Client eliminat definitivament.');
        }

        return $this->redirectToRoute('admin_client_index');
    }
}
