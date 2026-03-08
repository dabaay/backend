<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "=== SYSTEM USERS ===\n";
foreach(App\Models\User::all() as $u) {
    echo "- Username: {$u->username} | Role: {$u->role} | Full Name: {$u->full_name}\n";
}

echo "\n=== SYSTEM SETTINGS ===\n";
foreach(App\Models\SystemSetting::all() as $s) {
    echo "- {$s->setting_key}: {$s->setting_value}\n";
}

echo "\n=== PRODUCT INVENTORY ===\n";
foreach(App\Models\Product::all() as $p) {
    echo "- [{$p->product_code}] {$p->name} | Price: \${$p->selling_price} | Stock: {$p->current_stock} {$p->unit}\n";
}

echo "\n=== CUSTOMER LIST ===\n";
foreach(App\Models\Customer::all() as $c) {
    echo "- {$c->full_name} | Phone: {$c->phone} | Status: {$c->status}\n";
}
