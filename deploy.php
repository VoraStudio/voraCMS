<?php
/* ══════════════════════════════════════════════════════════════
   deploy.php — Post-pull steps per VoraCMS
   ══════════════════════════════════════════════════════════════
   Executat des del workflow de GitHub Actions (o manualment)
   després de git pull. Neteja caché i reinicia OPcache.
   Les migracions les executa bin/console al workflow.
   ══════════════════════════════════════════════════════════════ */

chdir(__DIR__);
$appEnv = 'prod';

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

/* ── 2. OPcache reset ── */
echo "\n>> Resetting OPcache...\n";
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo "   OPcache reset OK\n";
} else {
    echo "   OPcache not available\n";
}

echo "\n=== Deploy complete ===\n";
