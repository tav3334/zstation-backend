<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

echo "🚀 Exécution des migrations...\n";

$status = $kernel->call('migrate', [
    '--force' => true,
]);

echo $status === 0 ? "✅ Migrations terminées!\n" : "❌ Erreur lors des migrations\n";

exit($status);
