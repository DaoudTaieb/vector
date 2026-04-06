<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "--- COLONNES FREGLEMENTS ---\n";
$cols = DB::select("SELECT column_name FROM information_schema.columns WHERE table_name = 'freglements'");
foreach($cols as $c) echo $c->column_name . "\n";

echo "\n--- TABLES DETFREG? ---\n";
$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_name LIKE '%det%reg%'");
foreach($tables as $t) echo $t->table_name . "\n";

echo "\n--- COLONNES FREGLEMENTS PREMIERES LIGNES ---\n";
$sample = DB::table('freglements')->limit(1)->first();
if ($sample) print_r($sample);
else echo "Aucun règlement trouvé.\n";
