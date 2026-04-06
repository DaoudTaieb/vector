<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

function showColumns($table) {
    echo "--- $table ---\n";
    try {
        $columns = DB::select("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = ?", [$table]);
        if (count($columns) == 0) {
            echo "TABLE DOES NOT EXIST\n";
        } else {
            foreach ($columns as $c) {
                echo "- {$c->column_name} ({$c->data_type})\n";
            }
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}

showColumns('clients');
showColumns('cfactures');
showColumns('cbls');
showColumns('creglements');
showColumns('cavoirs');
showColumns('cbrs');
