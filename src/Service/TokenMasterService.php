<?php

/* ══════════════════════════════════════════════════════════════
   TokenMasterService — VoraCMS
   ══════════════════════════════════════════════════════════════
   Servei per generar JWT per a frontends públics sense login.

   Rep un domini, busca l'usuari que el té als seus allowed_domains,
   i genera un JWT vàlid per a aquell usuari.

   Ús:
     $token = $tokenMasterService->generateToken('vorastudio.cat');
     if ($token) { ... } else { 403 }
   ══════════════════════════════════════════════════════════════ */

namespace App\Service;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;

readonly class TokenMasterService
{
    public function __construct(
        private UserRepository $userRepo,
        private JWTTokenManagerInterface $jwtManager,
        private LoggerInterface $logger,
    ) {}

    /**ara quin
     * Genera un JWT per al domini donat.
     *
     * @param string $domain El domini del frontend (ex: vorastudio.cat)
     * @return string|null El JWT en format string, o null si el domini no està autoritzat
     */
    public function generateToken(string $domain): ?string
    {
        /* Normalitzar: treure www. per si el client ve amb prefix */
        $domain = preg_replace('/^www\./i', '', $domain);
        $user = $this->userRepo->findOneByDomain($domain);

        if (!$user) {
            $this->logger->warning('TokenMasterService: Domini no autoritzat', [
                'domain' => $domain,
            ]);
            return null;
        }

        try {
            return $this->jwtManager->create($user);
        } catch (\Throwable $e) {
            $this->logger->error('TokenMasterService: Error en generar el JWT', [
                'domain' => $domain,
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
