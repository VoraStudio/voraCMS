<?php

use App\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// CDMON: Apache+PHP-FPM no carga .env.local en producción.
// Symfony Runtime solo lee variables del sistema, no del archivo.
$_ENV_FILE = dirname(__DIR__).'/.env.local';
if (file_exists($_ENV_FILE)) {
    (new Symfony\Component\Dotenv\Dotenv())->overload($_ENV_FILE);
}

return static function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
