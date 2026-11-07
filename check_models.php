<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\VirtualTryOnModel::all() as $m) {
    echo "ID: {$m->id} | Name: {$m->name} | is_active: {$m->is_active}\n";
    echo "  photo: " . var_export($m->photo, true) . "\n";
    echo "  photo_url: " . var_export($m->photo_url, true) . "\n";
}

