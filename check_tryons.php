<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (App\Models\ProductVirtualTryOn::all() as $t) {
    echo "ID: {$t->id} | Product: {$t->product_id} | User: " . var_export($t->user_id, true) . " | Default: " . ($t->is_default ? 'YES' : 'NO') . " | Status: {$t->status}\n";
    echo "  photo: " . var_export($t->photo, true) . "\n";
    echo "  video: " . var_export($t->video, true) . "\n";
    echo "  photo_url: " . var_export($t->photo_url, true) . "\n";
    echo "  video_url: " . var_export($t->video_url, true) . "\n";
}

