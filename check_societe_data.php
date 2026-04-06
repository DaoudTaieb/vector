<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$societe = DB::table('societes')->first();
if ($societe) {
    echo "Données dans la table 'societes' :\n";
    print_r($societe);
} else {
    echo "La table 'societes' est vide.\n";
}
