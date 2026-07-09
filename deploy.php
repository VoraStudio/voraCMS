<?php
/* ══════════════════════════════════════════════════════════════
   deploy.php — Post-pull steps per VoraCMS
   ══════════════════════════════════════════════════════════════
   Executat des del workflow de GitHub Actions (o manualment)
   després de git pull. No requereix bin/console (evita
   proc_open() deshabilitat a CDMON).
   ══════════════════════════════════════════════════════════════ */

chdir(__DIR__);
$appEnv = 'prod';

require __DIR__ . '/vendor/autoload.php';

/* ── Load environment variables (CLI context doesn't inherit Apache env) ── */
if (file_exists(__DIR__ . '/.env')) {
    (new Symfony\Component\Dotenv\Dotenv())->loadEnv(__DIR__ . '/.env');
}

echo "=== Deploy VoraCMS ===\n\n";

/* ── 1. Cache: neteja física ── */
echo ">> Clearing cache...\n";
$cacheDir = __DIR__ . '/var/cache/' . $appEnv;
if (is_dir($cacheDir)) {
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($files as $file) {
        $file->isDir() ? @rmdir($file->getRealPath()) : @unlink($file->getRealPath());
    }
    echo "   Cache cleared: $cacheDir\n";
}

/* ── 2. Migrations ── */
echo "\n>> Running migrations...\n";

$kernel = new App\Kernel($appEnv, false);
$kernel->boot();

$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
$conn = $em->getConnection();

$migrationTable = 'doctrine_migration_versions';
$migrationsDir = __DIR__ . '/migrations';

// Ensure migration table exists
$conn->executeStatement("CREATE TABLE IF NOT EXISTS $migrationTable (
    version VARCHAR(191) NOT NULL,
    executed_at DATETIME DEFAULT NULL,
    execution_time INT DEFAULT NULL,
    PRIMARY KEY (version)
) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");

// Get all migration files
$files = glob($migrationsDir . '/Version*.php');
sort($files);

$executed = $conn->fetchFirstColumn("SELECT version FROM $migrationTable");
$count = 0;

foreach ($files as $file) {
    $version = pathinfo($file, PATHINFO_FILENAME);
    if (in_array($version, $executed, true)) {
        continue;
    }

    echo "   Applying: $version... ";
    $start = microtime(true);

    require_once $file;
    $class = 'DoctrineMigrations\\' . $version;
    $migration = new $class($conn->getDatabasePlatform());
    $migration->up($conn->createSchemaManager());

    $time = (int) ((microtime(true) - $start) * 1000);
    $conn->insert($migrationTable, [
        'version' => $version,
        'executed_at' => date('Y-m-d H:i:s'),
        'execution_time' => $time,
    ]);

    echo "OK ({$time}ms)\n";
    $count++;
}

if ($count === 0) {
    echo "   No pending migrations.\n";
} else {
    echo "\n   $count migration(s) applied.\n";
}

/* ── 3. OPcache reset ── */
echo "\n>> Resetting OPcache...\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "   OPcache reset OK\n";
} else {
    echo "   OPcache not available\n";
}

echo "\n=== Deploy complete ===\n";
