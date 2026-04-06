<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
$users = DB::table('users')->select('userid', 'login', 'password')->get();
echo json_encode($users, JSON_PRETTY_PRINT);
