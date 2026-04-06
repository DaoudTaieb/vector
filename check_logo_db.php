<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$columns = DB::select("SELECT table_name, column_name FROM information_schema.columns WHERE column_name ILIKE '%logo%'");
if (empty($columns)) {
    echo "Aucune colonne contenant 'logo' n'a été trouvée dans la base de données.\n";
} else {
    echo "Colonnes 'logo' trouvées :\n";
    print_r($columns);
}

// Check for societes table
$societes = DB::select("SELECT table_name FROM information_schema.tables WHERE table_name ILIKE '%societe%'");
if (!empty($societes)) {
    echo "\nTables 'societe' trouvées :\n";
    print_r($societes);
}
