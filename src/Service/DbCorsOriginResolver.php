<?php

/* ===========================================================
   DbCorsOriginResolver — Orígens CORS des de la base de dades
   ===========================================================
   Llegeix tots els allowed_domains de tots els usuaris i els
   retorna com a orígens permesos per al CORS.

   Així, quan un admin afegeix un domini a un usuari des del
   panell, el CORS s'actualitza automàticament sense tocar
   fitxers d'entorn.
   =========================================================== */

namespace App\Service;

use App\Contract\CorsOriginResolverInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;

readonly class DbCorsOriginResolver implements CorsOriginResolverInterface
{
    public function __construct(
        private UserRepository $userRepo,
    ) {}

    public function resolve(Request $request): array
    {
        return $this->userRepo->findAllAllowedDomains();
    }
}
