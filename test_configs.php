<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (\App\Models\AppConfig::where('key', 'like', '%try_on%')->get() as $c) {
    echo $c->key . " = " . var_export($c->value, true) . PHP_EOL;
}

