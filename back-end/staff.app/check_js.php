<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$ttd = App\Models\TandaTangan::all();
foreach ($ttd as $t) {
    echo "ID: {$t->id} | Nama: {$t->nama} | TDD: " . ($t->tdd ? 'exists' : 'null') . " | Gambar: {$t->gambar}\n";
}
