<?php

/* ===========================================================
   CorsOriginResolverInterface — Contracte per resoldre
   orígens CORS permesos per a cada request.
   =========================================================== */

namespace App\Contract;

use Symfony\Component\HttpFoundation\Request;

interface CorsOriginResolverInterface
{
    /** @return string[] */
    public function resolve(Request $request): array;
}
