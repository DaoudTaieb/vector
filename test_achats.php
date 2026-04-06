<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

$factures = DB::table('ffactures')->get();
$bls = DB::table('fbls')->get();

echo "Factures: " . count($factures) . "\n";
echo "BLs: " . count($bls) . "\n";

$facturesWithF = DB::table('ffactures')->join('fournisseurs', 'ffactures.fournisseurid', '=', 'fournisseurs.fournisseurid')->get();
$blsWithF = DB::table('fbls')->join('fournisseurs', 'fbls.fournisseurid', '=', 'fournisseurs.fournisseurid')->where('transfere', false)->get();

echo "Factures with FOURNISSEURS: " . count($facturesWithF) . "\n";
echo "BLs with FOURNISSEURS (non transférés): " . count($blsWithF) . "\n";

echo "ALL BLs with FOURNISSEURS: " . DB::table('fbls')->join('fournisseurs', 'fbls.fournisseurid', '=', 'fournisseurs.fournisseurid')->count() . "\n";
