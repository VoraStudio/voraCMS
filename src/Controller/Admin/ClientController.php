<?php

/* ===========================================================
   ClientController — Gestió de clients (CRUD).
   EXCLUSIU per a ROLE_ADMIN. Tots els mètodes
   requereixen aquest rol.

   Els clients són la unitat d'aïllament multi-tenant.
   Cada client té els seus propis ContentTypes, Entries,
   Media i Users independents.
   =========================================================== */

namespace App\Controller\Admin;

use App\Entity\Client;
use App\Entity\User;
use App\Repository\ClientRepository;
use App\Service\ClientProvisioner;
use App\Service\ClientScope;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/client')]
class ClientController extends AbstractController
{
    public function __construct(
        private readonly ClientScope $clientScope,
        private readonly EntityManagerInterface $em,
        private readonly ClientRepository $clientRepo,
        private readonly ClientProvisioner $clientProvisioner,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {}

    /* -----------------------------------------------------------
       index — Llista tots els clients amb estadístiques bàsiques
       (nombre d'entrades per client). Només accessible per
        admin.
       ----------------------------------------------------------- */
    #[Route('/', name: 'admin_client_index')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

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

        A més de les dades del client, el formulari inclou els
        camps per crear l'usuari administrador (email, password,
        rol). El ClientProvisioner s'encarrega de crear tant els
        ContentTypes base com l'usuari en una sola operació.
        ----------------------------------------------------------- */
    #[Route('/new', name: 'admin_client_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($request->isMethod('POST')) {
            /* ── Dades del client ── */
            $client = new Client();
            $client->setName($request->request->get('name'));
            $client->setSlug($request->request->get('slug'));
            $client->setActive($request->request->getBoolean('active'));

            /* ── Dades de l'usuari ── */
            $email = $request->request->get('user_email');
            $password = $request->request->get('user_password');
            $role = $request->request->get('user_role', 'ROLE_ADMIN');

            /* Validació: email i password obligatoris */
            if (empty($email) || empty($password)) {
                $this->addFlash('error', 'L\'email i la contrasenya de l\'usuari són obligatoris.');
                return $this->render('admin/client/new.html.twig');
            }

            $this->em->persist($client);

            try {
                /* El provisioner crea els ContentTypes base + l'usuari.
                   Es crida DESPRÉS de persistir el client perquè necessita
                   tenir ID assignat per les relacions. */
                $this->clientProvisioner->provisionWithUser($client, $email, $password, $role);
            } catch (UniqueConstraintViolationException $e) {
                $this->em->clear();
                $this->addFlash('error', 'Ja existeix un usuari amb aquest email per aquest client.');
                return $this->render('admin/client/new.html.twig');
            }

            $this->addFlash('success', 'Client i usuari creats correctament.');
            return $this->redirectToRoute('admin_client_index');
        }

        return $this->render('admin/client/new.html.twig');
    }

    /* -----------------------------------------------------------
        edit — Formulari d'edició de client (GET + POST).
        A més de les dades del client, permet gestionar l'usuari
        associat: canviar email, contrasenya i rol.

        Com que cada client té exactament 1 usuari, agafem el
        primer de la col·lecció. Si no en té (cas rar), es mostra
        el formulari per crear-lo.
        ----------------------------------------------------------- */
    #[Route('/{id}/edit', name: 'admin_client_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Client $client): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        // Obtenim l'usuari associat al client (si existeix)
        $user = $client->getUsers()->first() ?: null;

        if ($request->isMethod('POST')) {
            /* ── Dades del client ── */
            $client->setName($request->request->get('name'));
            $client->setActive($request->request->getBoolean('active'));

            /* El slug NO es pot canviar perquè és la clau d'URL
               i s'utilitza en rutes públiques. */

            /* ── Dades de l'usuari ── */
            $userEmail = $request->request->get('user_email');
            $userPassword = $request->request->get('user_password');
            $userRole = $request->request->get('user_role');

            if ($user && $userEmail) {
                /* L'usuari existeix → actualitzem dades */
                $user->setEmail($userEmail);

                if ($userRole) {
                    $user->setRoles([$userRole]);
                }

                /* Contrasenya: només actualitzar si s'ha introduït */
                if (!empty($userPassword)) {
                    $hashed = $this->passwordHasher->hashPassword($user, $userPassword);
                    $user->setPassword($hashed);
                }
            } elseif ($userEmail && !empty($request->request->get('user_password'))) {
                /* No hi ha usuari → en creem un de nou amb les dades del formulari */
                $newUser = new User();
                $newUser->setEmail($userEmail);
                $newUser->setName($client->getName());
                $newUser->setLocale('ca');
                $newUser->setActive(true);
                $newUser->setClient($client);
                $newUser->setRoles([$userRole ?: 'ROLE_ADMIN']);
                $hashed = $this->passwordHasher->hashPassword($newUser, $request->request->get('user_password'));
                $newUser->setPassword($hashed);
                $this->em->persist($newUser);
            }

            try {
                $this->em->flush();
            } catch (UniqueConstraintViolationException $e) {
                $this->em->refresh($client);
                $this->addFlash('error', 'Ja existeix un altre usuari amb aquest email per aquest client.');
                return $this->render('admin/client/edit.html.twig', [
                    'client' => $client,
                    'user' => $user,
                ]);
            }

            $this->addFlash('success', 'Client actualitzat correctament.');
            return $this->redirectToRoute('admin_client_index');
        }

        return $this->render('admin/client/edit.html.twig', [
            'client' => $client,
            'user' => $user,
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
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $tokenId = 'delete_client_' . $client->getId();
        if ($this->isCsrfTokenValid($tokenId, $request->request->get('_token'))) {
            $this->em->remove($client);
            $this->em->flush();
            $this->addFlash('success', 'Client eliminat definitivament.');
        }

        return $this->redirectToRoute('admin_client_index');
    }
}
