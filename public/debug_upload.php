<?php
require_once __DIR__ . "/../vendor/autoload.php";

$kernel = new \App\Kernel("dev", true);
$request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
$response = $kernel->handle($request);

// Debug: dump request info
echo "METHOD: " . $request->getMethod() . "\n";
echo "FILES: " . count($request->files->all()) . "\n";
foreach ($request->files->all() as $key => $file) {
    if ($file) {
        echo "  $key: " . $file->getClientOriginalName() . " (" . $file->getSize() . " bytes)\n";
    } else {
        echo "  $key: null\n";
    }
}
echo "POST: " . json_encode($request->request->all()) . "\n";
