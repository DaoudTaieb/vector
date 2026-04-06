<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$tables = DB::select("SELECT table_name, table_type FROM information_schema.tables WHERE table_schema = 'public'");
foreach($tables as $t) {
    $name = $t->table_name;
    if (stripos($name, 'facture') !== false || stripos($name, 'achat') !== false || stripos($name, 'bl') !== false) {
        try {
            $count = DB::table($name)->count();
            echo "MATCH: $name (" . $t->table_type . ") -> $count rows\n";
        } catch (\Exception $e) {
            echo "MATCH: $name -> Error counting\n";
        }
    }
}
