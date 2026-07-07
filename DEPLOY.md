# VoraCMS — Deploy a Producción (CDMON Senior)

> **URL:** https://voracms.voradata.cat  
> **Servidor:** CDMON Senior (hosting compartido)  
> **PHP:** 8.4 | **MySQL:** MariaDB | **Apache:** PHP-FPM (proxy_fcgi)

---

## Índice

- [Primer deploy](#primer-deploy)
- [Post-deploy: ajustes necesarios](#post-deploy-ajustes-necesarios)
- [Deploys posteriores](#deploys-posteriores)
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
composer install --no-dev --optimize-autoloader --no-interaction
```

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
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --no-debug
```

### 10. Configurar .htaccess

CDMON usa PHP-FPM (`proxy_fcgi`), el `.htaccess` estándar de Symfony causa bucles de redirección.

**`public/.htaccess`:**

```apache
DirectoryIndex index.php
FallbackResource /index.php
```

### 11. Permisos

```bash
chmod -R 775 var/cache var/log
chmod -R 775 config/jwt/*.pem
```

---

## Post-deploy: ajustes necesarios

### □ CORS para frontends externos

Si frontends estáticos (Victoria Taylor, Palmito House) llaman a la API desde otro dominio, hay que permitir CORS.

Opción A — Apache (recomendado si los dominios son fijos):

```apache
# public/.htaccess
Header always set Access-Control-Allow-Origin "https://victoriataylor.com"
Header always set Access-Control-Allow-Methods "GET, POST, OPTIONS"
Header always set Access-Control-Allow-Headers "Authorization, Content-Type"
```

Opción B — Bundle NelmioCors (más flexible):

```bash
composer require nelmio/cors-bundle
```

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

---

## Deploy automático con GitHub Actions

Cada `git push` a la rama `main` ejecuta automáticamente el deploy vía GitHub Actions.

**Workflow:** `.github/workflows/deploy.yml`

**Qué necesita (configurar una vez en GitHub):**

| Secret | Valor |
|---|---|
| `SSH_HOST` | IP del servidor |
| `SSH_USER` | Usuario SSH |
| `SSH_KEY` | Clave privada SSH (generada en el servidor con `ssh-keygen`) |

Los secrets se configuran en: **GitHub → Repo → Settings → Secrets and variables → Actions**

El workflow ejecuta:

```bash
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --no-debug
chmod -R 775 var/cache var/log
```

Puedes ver el estado en: `https://github.com/VoraStudio/voraCMS/actions`

## Deploys manuales (alternativa SSH)

Cuando no funcione GitHub Actions o necesites hacerlo manual:

```bash
ssh USUARIO_SSH@SERVER_IP
cd /web/voracms
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
php bin/console doctrine:migrations:migrate --env=prod --no-interaction --no-debug
chmod -R 775 var/cache var/log
```

Si el cambio es solo CSS/JS/config sin nuevas dependencias:

```bash
ssh USUARIO_SSH@SERVER_IP
cd /web/voracms
git pull origin main
php bin/console cache:clear --env=prod --no-debug
php bin/console cache:warmup --env=prod --no-debug
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

### Error 500 — Internal Server Error

1. Mirar logs de Apache: `tail -50 /errors.log`
2. Mirar logs de Symfony: `tail -50 /web/voracms/var/log/prod.log`
3. Si no hay logs Symfony, asegurar `APP_ENV=prod` en `.env` y crear `var/log/` con permisos 775

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

> **Última actualización:** Julio 2026  
> **Deploy realizado por:** Pau (Vora Studio)
