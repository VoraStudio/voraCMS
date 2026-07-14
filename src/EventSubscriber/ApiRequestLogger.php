<?php

/* ══════════════════════════════════════════════════════════════
   ApiRequestLogger — VoraCMS
   ══════════════════════════════════════════════════════════════
   Registra TOTES les crides a /api/* (GET, POST, etc.) a la
   taula api_request_log amb finalitats de monitorització.

   S'executa al final (priority -10) perquè la resposta ja tingui
   el status code definitiu i el JWT ja estigui processat per Lexik.

   Exclou:
     - /api/auth/* (gestionat pel firewall de login)
     - OPTIONS preflight (soroll CORS)
   ══════════════════════════════════════════════════════════════ */

namespace App\EventSubscriber;

use App\Entity\ApiRequestLog;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::RESPONSE, priority: -10)]
readonly class ApiRequestLogger
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private ProjectRepository $projectRepo,
    ) {}

    public function __invoke(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $path = $request->getPathInfo();

        /* ── Només API ── */
        if (!str_starts_with($path, '/api')) {
            return;
        }

        /* ── Excloure auth i OPTIONS ── */
        if (str_starts_with($path, '/api/auth') || $request->isMethod('OPTIONS')) {
            return;
        }

        $response = $event->getResponse();
        $start = $request->server->get('REQUEST_TIME_FLOAT');

        $log = new ApiRequestLog();
        $log->setDomain($request->getHost());
        $log->setEndpoint($path);
        $log->setMethod($request->getMethod());
        $log->setStatusCode($response->getStatusCode());
        $log->setIp($request->getClientIp() ?? '127.0.0.1');
        $log->setUserAgent($request->headers->get('User-Agent'));
        $log->setOrigin($request->headers->get('Origin'));
        $log->setReferer($request->headers->get('Referer'));
        $log->setXForwardedFor($request->headers->get('X-Forwarded-For'));

        /* ── Extreure JTI del JWT si existeix ── */
        $log->setTokenJti($this->extractJti($request));

        /* ── Temps de resposta ── */
        if ($start) {
            $log->setResponseTimeMs((int) round((microtime(true) - (float) $start) * 1000));
        }

        /* ── Detectar projecte des de la URL ── */
        $log->setProject($this->detectProject($path));

        /* ── Marcar com a token endpoint si escau ── */
        if ($path === '/api/public/token') {
            $this->enrichTokenEndpoint($log, $response);
        }

        try {
            $this->em->persist($log);
            $this->em->flush();
        } catch (\Throwable $e) {
            $this->logger->error('ApiRequestLogger: Error en desar el registre de petició', [
                'endpoint' => $path,
                'domain' => $request->getHost(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /* ── Detecta el projecte des de la URL de l'API pública ── */
    private function detectProject(string $path): ?\App\Entity\Project
    {
        if (preg_match('#^/api/public/(?P<slug>[a-z0-9_-]+)/#i', $path, $m)) {
            $slug = $m['slug'];
            if (!in_array($slug, ['token', 'artistes', 'web'], true)) {
                return $this->projectRepo->findBySlug($slug);
            }
        }
        return null;
    }

    /* ── Extreure jti del payload JWT (només base64, sense verificar firma) ── */
    private function extractJti($request): ?string
    {
        $header = $request->headers->get('Authorization');
        if (!$header || !str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode($parts[1]), true);

        if (!is_array($payload)) {
            return null;
        }

        return $payload['jti'] ?? null;
    }

    /* ── Enriquir log del token endpoint amb granted/deny_reason ── */
    private function enrichTokenEndpoint(ApiRequestLog $log, $response): void
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode === 200) {
            $log->setGranted(true);

            /* Extreure JTI del token emès des de la resposta JSON */
            $content = $response->getContent();
            if ($content) {
                $data = json_decode($content, true);
                if (is_array($data) && isset($data['token'])) {
                    $parts = explode('.', $data['token']);
                    if (count($parts) === 3) {
                        $payload = json_decode(base64_decode($parts[1]), true);
                        if (is_array($payload) && isset($payload['jti'])) {
                            $log->setTokenJti($payload['jti']);
                        }
                    }
                }
            }
        } elseif ($statusCode === 403) {
            $log->setGranted(false);

            /* Intentar deduir el motiu de la resposta */
            $content = $response->getContent();
            if ($content) {
                $data = json_decode($content, true);
                if (is_array($data) && isset($data['error'])) {
                    $log->setDenyReason($data['error'] === 'Domini no autoritzat'
                        ? 'domain_not_allowed'
                        : 'unknown');
                }
            }
        }
    }
}
