<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT tablename FROM pg_tables WHERE schemaname = 'public'");

echo "Compte des tables:\n";
foreach ($tables as $t) {
    $count = DB::table($t->tablename)->count();
    if ($count > 0) {
        echo $t->tablename . ": " . $count . "\n";
    }
}
