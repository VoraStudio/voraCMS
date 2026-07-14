# VoraCMS — Deploy a Producción (CDMON Senior)

> **URL:** https://voracms.voradata.cat  
> **Servidor:** CDMON Senior (hosting compartido)  
> **PHP:** 8.4 | **MySQL:** MariaDB | **Apache:** PHP-FPM (proxy_fcgi)

---

## Índice

- [Primer deploy](#primer-deploy)
- [Post-deploy: ajustes necesarios](#post-deploy-ajustes-necesarios)
- [Deploy automático (GitHub Actions)](#deploy-automático-con-github-actions)
- [Deploys manuales](#deploys-manuales-alternativa-ssh)
- [Backup de la base de datos](#backup-de-la-base-de-datos)
- [Resolución de problemas comunes](#resolución-de-problemas-comunes)

---

## Primer deploy

### 1. Crear subdominio en CDMON

- **Subdominio:** `voracms.voradata.cat`
- **Carpeta destino:** `voradata.cat/web/voracms/public`

### 2. Crear base de datos MySQL

Desde el panel de CDMON → Bases de datos → MySQL:

- **BD:** `NOMBRE_BD`
- **Usuario:** `USUARIO_BD`
- **Contraseña:** la asignada en el panel

### 3. Importar datos desde local

En local (PowerShell como administrador):

```powershell
C:\xampp\mysql\bin\mysqldump.exe -u root voracms > voracms-export.sql
```

**⚠️ No uses `>` desde PowerShell directamente** (corrompe codificación). Usa:

```powershell
cmd /c "C:\xampp\mysql\bin\mysqldump.exe -u root voracms > %USERPROFILE%\Desktop\voracms-export.sql"
```

Importar en CDMON vía phpMyAdmin (o SSH).

### 4. Conectarse por SSH

```bash
ssh USUARIO_SSH@SERVER_IP
```

Acceso SSH debe activarse desde el panel CDMON → Gestión de ficheros → SSH/SFTP.

### 5. Clonar el repositorio

```bash
cd voradata.cat/web
git clone https://USUARIO:TOKEN@github.com/VoraStudio/voraCMS.git voracms
cd voracms
git remote set-url origin https://USUARIO@github.com/VoraStudio/voraCMS.git
```

> Usar **Personal Access Token** de GitHub con permiso `repo`.

### 6. Configurar entorno

```bash
cat > .env << 'EOF'
APP_ENV=prod
APP_SECRET=<GENERAR_NUEVO_CON: openssl rand -hex 32>
DEFAULT_URI=https://voracms.voradata.cat
DATABASE_URL="mysql://usuario:password@127.0.0.1:3306/voracms?serverVersion=mariadb-10.4.32&charset=utf8mb4"
EOF
```

```bash
cat > .env.local << 'ENVEOF'
APP_ENV=prod
APP_SECRET=<MISMO_QUE_ENV>
DEFAULT_URI=https://voracms.voradata.cat
DATABASE_URL="mysql://usuario:password@127.0.0.1:3306/voracms?serverVersion=mariadb-10.4.32&charset=utf8mb4"
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=<PASSPHRASE_JWT>
ENVEOF
```

> **⚠️ Caracteres especiales en contraseña:** `@` → `%40`, `!` → `%21`, `#` → `%23`, `$` → `%24`

### 7. Instalar dependencias

```bash
composer install --no-dev --optimize-autoloader --no-interaction --no-scripts
```

> ⚠️ CDMON tiene `proc_open()` deshabilitado. El flag `--no-scripts` evita que los scripts de composer fallen. Si algún script (post-install, post-update) intenta ejecutar comandos externos, se saltan.

### 8. Generar JWT keys

```bash
mkdir -p config/jwt
```

Si `openssl` CLI no está disponible (CDMON no lo tiene), generar con PHP:

```bash
cat > gen-jwt.php << 'PHPEOF'
<?php
$config = ['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA, 'config' => '/tmp/openssl.cnf'];
if (!file_exists('/tmp/openssl.cnf')) {
    file_put_contents('/tmp/openssl.cnf', "[req]\ndistinguished_name = req_distinguished_name\n[req_distinguished_name]\n");
}
$key = openssl_pkey_new($config);
openssl_pkey_export($key, $privkey, '<PASSPHRASE>');
file_put_contents('config/jwt/private.pem', $privkey);
$pub = openssl_pkey_get_details($key);
file_put_contents('config/jwt/public.pem', $pub['key']);
echo "JWT keys generated\n";
PHPEOF
php gen-jwt.php
```

### 9. Cache y migrations

```bash
export COLUMNS=120 LINES=40
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --no-debug
```

> ⚠️ `COLUMNS=120` evita que Symfony Console cridi a `tput cols` via `proc_open()` (deshabilitat a CDMON).

### 10. Configurar .htaccess

CDMON usa PHP-FPM (`proxy_fcgi`). Solo `FallbackResource` funciona. `mod_rewrite` causa bucles de redirección.

**`public/.htaccess`:**

```apache
SetEnv DEFAULT_URI "https://voracms.voradata.cat"
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1

DirectoryIndex index.php
FallbackResource /index.php
```

> ⚠️ **CRÍTICO**: `SetEnv DEFAULT_URI` es necesario porque Apache+PHP-FPM no carga
> `.env.local` en producción. El `.htaccess` usa `FallbackResource`, NO `mod_rewrite`
> (que causa "10 internal redirects" en CDMON).

### 11. Proteger dotfiles

Afegir al `.htaccess` de l'arrel del projecte (si no existeix, crear-lo):

```apache
# Bloquejar accés a fitxers ocults (seguretat defense-in-depth)
RewriteEngine On
RewriteRule "(^|/)\." - [F]
```

> ⚠️ Això evita que `.env`, `.env.local`, `.jwt_cache` (si existeix) siguin accessibles via web.

### 12. Usuari vorastudio (ROLE_MOD)

El frontend (VoraStudio) necessita un usuari amb rol **ROLE_MOD** per obtenir el Master Token.

⚠️ **No carregar els fixtures** — tenen un bug de doble hash que invalida la contrasenya.

Crear manualment des del panell admin:
1. Entrar a `https://voracms.voradata.cat/admin`
2. Usuaris → Crear
3. Email: `vora@vora.es`, Password: `<la que vulguis>`, Rol: `ROLE_MOD`
4. Un cop creat, editar l'usuari i posar **IPs permeses** → `["134.0.10.83"]`

> L'IP `134.0.10.83` és la IP de sortida de vorastudio.cat (confirmada amb `check-ip.php`).

### 13. Permisos

```bash
chmod -R 777 var/cache var/log
chmod -R 775 config/jwt/*.pem
```

> ⚠️ `777` en `var/` porque Apache corre como un usuario distinto al SSH (CDMON compartido).

---

## Post-deploy: ajustes necesarios

### □ CORS para frontends externos

El CORS se resuelve automáticamente desde la base de datos. Cuando un admin añade
dominios a un usuario desde el panel (**Admin → Usuarios → Editar → Dominios permesos**),
el sistema CORS (`DbCorsOriginResolver`) los reconoce sin tocar ningún archivo.

No hace falta configurar nada en `.env`. Si el CORS no funciona para un nuevo frontend:

1. Entrar al admin → Usuarios → Editar el usuario correspondiente
2. Asegurar que el campo **Dominios permesos** incluye el dominio del frontend
3. Esperar a que el cache de Doctrine se refresque (o ejecutar `php bin/console cache:clear --env=prod`)

> ⚠️ El sistema CORS anterior (Apache `Header set`) ya no funciona porque `CorsSubscriber` sobreescribe els headers. Siempre usar la variable de entorno.

### □ Configurar PHP desde panel CDMON

Entrar a admin.cdmon.com → hosting → PHP → asegurar:

- **PHP versión:** 8.4 (o 8.3 mínimo)
- **max_execution_time:** 120 segundos
- **upload_max_filesize:** 64M
- **post_max_size:** 64M
- **memory_limit:** 256M

### □ IP dedicada (opcional)

CDMON ofrece IP dedicada por 12€/año. Recomendable si:
- Se necesita SSL sin compartir
- Se quiere evitar que otros sitios en el mismo servidor afecten al SEO

### □ SSL

CDMON incluye Let's Encrypt wildcard auto-renovable. Verificar que está activo para `voracms.voradata.cat`.

### □ Cron para sync de content types

Si se usa el comando `app:sync-content-types`, añadir cron en panel CDMON:

```bash
php /web/voracms/bin/console app:sync-content-types --env=prod --no-debug
```

### □ Monitorizar logs

```bash
tail -f /errors.log                       # Log de Apache
tail -f /web/voracms/var/log/prod.log     # Log de Symfony (cuando exista)
```

### □ Verificación post-deploy

Después de cada deploy, verificar que el CMS responde:

```bash
# Health check simple
curl -s -o /dev/null -w "%{http_code}" https://el-teu-cms.com/admin/login

# Health check API pública
curl -s -o /dev/null -w "%{http_code}" https://el-teu-cms.com/api/public/token

# Debe responder 200 en ambos
```

---

## Problemas conocidos (gotchas)

### Fixtures rotos (doble hash)
Los fixtures de `AppFixtures.php` tienen un bug que aplica `password_hash()` dos veces. **No cargar nunca `doctrine:fixtures:load`** en producción. Si necesitas un usuario nuevo, créalo desde el admin.

### CmsClient no se despliega con GitHub Actions
El workflow solo despliega el backend Symfony (el directorio del CMS). Los cambios en el frontend (`CmsClient.php`, templates PHP, CSS, JS) se suben **manualmente** por FTP o SSH. Después de actualizar manualmente, ejecutar en el servidor:

```bash
cd /ruta/al/cms
php deploy.php
```

### JWT cache en el frontend
El `CmsClient.php` cacheja el JWT a `sys_get_temp_dir()/vorastudio_jwt_{md5(__DIR__)}`. Si por algún motivo el JWT se corrompe (ej: se regeneran las keys del CMS), borrar el archivo de cache en el servidor:

```bash
rm -f /tmp/*_jwt_*
```

### IP de salida del frontend
Si el frontend cambia de hosting o IP, actualizar el campo **IPs permeses** del usuario en el admin (Admin → Usuarios → Editar → IPs permeses). El sistema (`DbCorsOriginResolver`, `VisitController`) lo recoge automáticamente. Para verificar la IP de salida actual:

```bash
# Ejecutar desde el servidor del frontend:
curl -s https://api.ipify.org
```

### Cambiar TTL del JWT
El JWT caduca a la hora (`token_ttl: 3600`). Si algún frontend necesita más tiempo, cambiar en `config/packages/lexik_jwt_authentication.yaml`. Recordar que un TTL más largo = ventana más grande de riesgo si el token se filtra.

---

## Deploy automático con GitHub Actions

Cada `git push` a la rama `main` ejecuta automáticamente el deploy vía GitHub Actions.

**Workflow:** `.github/workflows/deploy.yml`

**Qué necesita (configurar una vez en GitHub):**

| Secret | Valor |
|---|---|
| `SSH_HOST` | IP del servidor |
| `SSH_USER` | Usuario SSH |
| `SSH_KEY` | Clave privada SSH |
| `SITE_URL` | URL del CMS (ej: `https://voracms.voradata.cat`) — para el health check |

Los secrets se configuran en: **GitHub → Repo → Settings → Secrets and variables → Actions**

**Flujo del deploy:**

| Paso | Dónde | Qué |
|------|-------|-----|
| 1 | GitHub Runner | `composer install --no-scripts` (instala dependencias sin `proc_open`) |
| 2 | GitHub Runner | `rsync vendor/` al servidor (evita `proc_open` en CDMON) |
| 3 | Servidor | `git pull origin main` |
| 4 | Servidor | `cp .env.local .env` (Apache necesita `.env`) |
| 5 | Servidor | `chmod -R 777 var/cache var/log` |
| 6 | Servidor | `source .env.local && COLUMNS=120 php deploy.php` (neteja caché física + OPcache reset) |
| 7 | Servidor | `COLUMNS=120 php bin/console doctrine:migrations:migrate` (falla si hi ha error — atura el deploy) |
| 8 | Servidor | `COLUMNS=120 php bin/console cache:warmup --env=prod` (regenera caché de Symfony) |
| 9 | Servidor | `chmod -R 777 var/cache var/log` + `php -r "opcache_reset();"` |
| 10 | GitHub Runner | Health check: `curl $SITE_URL/admin/login` → espera `200` |

> ⚠️ Si el health check falla, el workflow se marca como fallido. No hay rollback automático: revisar logs y corregir manualmente.

> ⚠️ CDMON tiene `proc_open()` deshabilitado. Por eso el `composer install` se ejecuta
> en el runner de GitHub (no en el servidor), y los comandos CLI llevan `COLUMNS=120`
> para evitar que Symfony Console llame a `tput cols` vía `proc_open()`.

Puedes ver el estado en: `https://github.com/VoraStudio/voraCMS/actions`

## Deploys manuales (alternativa SSH)

Cuando no funcione GitHub Actions o necesites hacerlo manual:

```bash
ssh USUARIO_SSH@SERVER_IP
cd /web/voracms
git pull origin main
cp .env.local .env
chmod -R 777 var/cache var/log
export COLUMNS=120 LINES=40
source .env.local
php deploy.php
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --no-debug
php bin/console cache:warmup --env=prod --no-debug
chmod -R 777 var/cache var/log
php -r "opcache_reset();"
```

---

## Backup de la base de datos

```bash
# Exportar (desde SSH en CDMON)
mysqldump -h 127.0.0.1 -u USUARIO_BD -p NOMBRE_BD > ~/backup-voracms-$(date +%Y%m%d).sql

# O desde local (si tienes acceso remoto configurado)
ssh USUARIO_SSH@SERVER_IP "mysqldump -h 127.0.0.1 -u USUARIO_BD -p'PASSWORD_BD' NOMBRE_BD" > backup-voracms.sql
```

---

## Resolución de problemas comunes

### Error 401 "Authentication required" en llamadas API

La API devuelve 401 aunque se envíe el token Bearer correcto.

**Causa:** Apache + PHP-FPM no pasa el header `Authorization` a PHP.

**Solución:** Verificar que `public/.htaccess` tenga la línea:

```apache
SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
```

Si está, forzar recarga de Apache o esperar unos minutos (CDMON cachea la configuración).

### Error 500 — Internal Server Error

1. Mirar logs de Apache: `tail -50 /errors.log`
2. Mirar logs de Symfony: `tail -50 /web/voracms/var/log/prod.log`
3. Si no hay logs Symfony, asegurar `APP_ENV=prod` en `.env` y crear `var/log/` con permisos 775
4. Comprobar migraciones pendientes: `php bin/console doctrine:migrations:status --env=prod --no-debug`

### Bucle de redirecciones (10 internal redirects)

CDMON con `mod_rewrite` + `FallbackResource` conflictivo. Solución:

```apache
# public/.htaccess
DirectoryIndex index.php
FallbackResource /index.php
```

### "No route found" en producción

Asegurar que `config/routes.yaml` existe y que los controladores tienen atributos `#[Route]` correctos. Limpiar caché:

```bash
php bin/console cache:clear --env=prod --no-debug
```

### Error de conexión MySQL

- Host correcto: `127.0.0.1` (no `localhost`)
- Verificar que la BD existe y el usuario tiene permisos
- Caracteres especiales en contraseña URL-encoded

### "You have requested a non-existent parameter" en cache:clear

Los `%` en la URL de la BD se interpretan como parámetros Symfony. Escapar con `%%`:

```bash
# Ejemplo: si la password tiene %40, cambiarlo a %%40
# Ver y corregir .env y .env.local
```

---

## Datos de conexión

| Recurso | Dato |
|---|----|
| SSH | `ssh USUARIO_SSH@SERVER_IP` |
| FTP | `voracms.voradata.cat` puerto 21 |
| BD Host | `127.0.0.1` |
| BD Name | `NOMBRE_BD` |
| BD User | `USUARIO_BD` |
| URL CMS | `https://voracms.voradata.cat` |
| Admin | `https://voracms.voradata.cat/admin/login` |
| Repo | `https://github.com/VoraStudio/voraCMS.git` |
| GitHub Actions | `https://github.com/VoraStudio/voraCMS/actions` |

> Los valores reales (IP, usuarios, contraseñas) se gestionan fuera del repo:  
> - Secrets de GitHub Actions para CI/CD  
> - `.env.local` en el servidor para la configuración de Symfony  
> - Panel de CDMON para credenciales FTP/SSH/BD

---

> **Última actualización:** Julio 2026 (revisión seguridad + health check)  
> **Deploy realizado por:** Pau (Vora Studio)
