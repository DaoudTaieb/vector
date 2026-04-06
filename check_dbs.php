<?php
$conn = pg_connect("host=127.0.0.1 port=5432 dbname=postgres user=postgres password=mydb171819");
if ($conn) {
    $result = pg_query($conn, "SELECT datname FROM pg_database WHERE datistemplate = false");
    echo "--- DATABASES ---\n";
    while ($row = pg_fetch_assoc($result)) {
        echo $row['datname'] . "\n";
    }
    pg_close($conn);
}

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

$row = DB::table('tempfiche')->first();
if ($row) {
    echo "\n--- TEMPFICHE ---\n";
    print_r($row);
}
